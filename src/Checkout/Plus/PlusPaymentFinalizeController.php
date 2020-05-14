<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\SwagPayPal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PlusPaymentFinalizeController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    /**
     * @var AsynchronousPaymentHandlerInterface
     */
    private $paymentHandler;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepo,
        AsynchronousPaymentHandlerInterface $paymentHandler
    ) {
        $this->orderTransactionRepo = $orderTransactionRepo;
        $this->paymentHandler = $paymentHandler;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/paypal/plus/payment/finalize-transaction", name="paypal.plus.payment.finalize.transaction", methods={"GET"}, defaults={"auth_required"=false})
     *
     * @throws InvalidTransactionException
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalizeTransaction(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        if ($request->query->getBoolean('cancel')) {
            throw new CustomerCanceledAsyncPaymentException(
                '',
                'Customer canceled the payment on the PayPal page'
            );
        }

        $paymentId = $request->query->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);

        $criteria = new Criteria();
        $criteria->addAssociation('order');
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter(
                        \sprintf('customFields.%s', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID),
                        $paymentId
                    ),
                    new NotFilter(
                        NotFilter::CONNECTION_AND,
                        [
                            new EqualsFilter(
                                \sprintf('customFields.%s', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID),
                                null
                            ),
                        ]
                    ),
                ]
            )
        );
        $result = $this->orderTransactionRepo->search($criteria, $salesChannelContext->getContext());

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $result->getEntities()->first();

        if ($orderTransaction === null) {
            throw new InvalidTransactionException('');
        }
        $order = $orderTransaction->getOrder();

        if ($order === null) {
            throw new InvalidTransactionException('');
        }

        $paymentTransactionStruct = new AsyncPaymentTransactionStruct($orderTransaction, $order, '');

        $this->paymentHandler->finalize($paymentTransactionStruct, $request, $salesChannelContext);

        $finishUrl = $this->generateUrl('frontend.checkout.finish.page', [
            'orderId' => $orderTransaction->getOrderId(),
            PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID => true,
        ]);

        return new RedirectResponse($finishUrl);
    }
}
