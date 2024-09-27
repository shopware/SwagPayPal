<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\SwagPayPal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement.
 */
#[Package('checkout')]
class PlusPaymentFinalizeController extends AbstractController
{
    private RouterInterface $router;

    private EntityRepository $orderTransactionRepo;

    private AsynchronousPaymentHandlerInterface $paymentHandler;

    private OrderTransactionStateHandler $transactionStateHandler;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderTransactionRepo,
        AsynchronousPaymentHandlerInterface $paymentHandler,
        OrderTransactionStateHandler $transactionStateHandler,
        RouterInterface $router,
        LoggerInterface $logger,
    ) {
        $this->orderTransactionRepo = $orderTransactionRepo;
        $this->paymentHandler = $paymentHandler;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * @throws PaymentException
     */
    #[Route(path: '/paypal/plus/payment/finalize-transaction', name: 'payment.paypal.plus.finalize.transaction', methods: ['GET'], defaults: ['auth_required' => false, '_routeScope' => ['storefront']])]
    public function finalizeTransaction(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $token = $request->query->get('token');
        $this->logger->debug('Starting with token {token}.', ['token' => $token]);

        $criteria = new Criteria();
        $criteria->addAssociation('order');
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter(
                        \sprintf('customFields.%s', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TOKEN),
                        $token
                    ),
                    new NotFilter(
                        NotFilter::CONNECTION_AND,
                        [
                            new EqualsFilter(
                                \sprintf('customFields.%s', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TOKEN),
                                null
                            ),
                        ]
                    ),
                ]
            )
        );

        $context = $salesChannelContext->getContext();
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepo->search($criteria, $context)->getEntities()->first();

        if ($orderTransaction === null) {
            throw PaymentException::invalidTransaction('');
        }
        $order = $orderTransaction->getOrder();

        if ($order === null) {
            throw PaymentException::invalidTransaction($orderTransaction->getId());
        }

        $paymentTransactionStruct = new AsyncPaymentTransactionStruct($orderTransaction, $order, '');

        $orderId = $order->getId();
        $changedPayment = $request->query->getBoolean('changedPayment');
        $finishUrl = $this->router->generate('frontend.checkout.finish.page', [
            'orderId' => $orderId,
            PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID => true,
            'changedPayment' => $changedPayment,
        ]);

        try {
            $this->logger->debug('Forwarding to payment handler.');
            $this->paymentHandler->finalize($paymentTransactionStruct, $request, $salesChannelContext);
        } catch (PaymentException $paymentException) {
            $this->logger->warning(
                '{message}. Redirecting to confirm page.',
                ['message' => $paymentException->getMessage(), 'error' => $paymentException]
            );
            $finishUrl = $this->redirectToConfirmPageWorkflow(
                $paymentException,
                $context,
                $orderId
            );
        }

        return new RedirectResponse($finishUrl);
    }

    /**
     * @throws PaymentException
     */
    private function redirectToConfirmPageWorkflow(
        PaymentException $paymentException,
        Context $context,
        string $orderId,
    ): string {
        $transactionId = $paymentException->getParameter('orderTransactionId');

        if (!$transactionId) {
            throw PaymentException::invalidTransaction('');
        }

        $errorUrl = $this->router->generate('frontend.account.edit-order.page', ['orderId' => $orderId]);

        if ($paymentException->getErrorCode() === PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL) {
            $this->transactionStateHandler->cancel(
                $transactionId,
                $context
            );
            $urlQuery = \parse_url($errorUrl, \PHP_URL_QUERY) ? '&' : '?';

            return \sprintf('%s%serror-code=%s', $errorUrl, $urlQuery, $paymentException->getErrorCode());
        }

        $this->logger->error(
            $paymentException->getMessage(),
            ['orderTransactionId' => $transactionId, 'error' => $paymentException]
        );
        $this->transactionStateHandler->fail(
            $transactionId,
            $context
        );
        $urlQuery = \parse_url($errorUrl, \PHP_URL_QUERY) ? '&' : '?';

        return \sprintf('%s%serror-code=%s', $errorUrl, $urlQuery, $paymentException->getErrorCode());
    }
}
