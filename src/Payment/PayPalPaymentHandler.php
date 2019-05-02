<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\PayPal\PaymentStatus;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    public const PAYPAL_REQUEST_PARAMETER_PAYER_ID = 'PayerID';
    public const PAYPAL_REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var PaymentBuilderInterface
     */
    private $paymentBuilder;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    public function __construct(
        DefinitionInstanceRegistry $definitionRegistry,
        PaymentResource $paymentResource,
        PaymentBuilderInterface $paymentBuilder,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderTransactionDefinition $orderTransactionDefinition
    ) {
        $this->orderTransactionRepo = $definitionRegistry->getRepository($orderTransactionDefinition->getEntityName());
        $this->paymentResource = $paymentResource;
        $this->paymentBuilder = $paymentBuilder;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $payment = $this->paymentBuilder->getPayment($transaction, $salesChannelContext);

        $context = $salesChannelContext->getContext();
        try {
            $response = $this->paymentResource->create($payment, $context);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with PayPal' . PHP_EOL . $e->getMessage()
            );
        }

        $data = [
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => [
                SwagPayPal::PAYPAL_TRANSACTION_CUSTOM_FIELD_NAME => $response->getId(),
            ],
        ];
        $this->orderTransactionRepo->update([$data], $context);

        return new RedirectResponse($response->getLinks()[1]->getHref());
    }

    /**
     * @throws AsyncPaymentFinalizeException
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $transactionId = $transaction->getOrderTransaction()->getId();

        if ($request->query->getBoolean('cancel')) {
            throw new CustomerCanceledAsyncPaymentException(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
        }

        $payerId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
        $paymentId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);
        $context = $salesChannelContext->getContext();
        try {
            $response = $this->paymentResource->execute($payerId, $paymentId, $context);
        } catch (\Exception $e) {
            throw new AsyncPaymentFinalizeException(
                $transactionId,
                'An error occurred during the communication with PayPal' . PHP_EOL . $e->getMessage()
            );
        }

        $paymentState = $this->getPaymentState($response);

        // apply the payment status if its completed by PayPal
        if ($paymentState === PaymentStatus::PAYMENT_COMPLETED) {
            $this->orderTransactionStateHandler->pay($transactionId, $context);
        } else {
            $this->orderTransactionStateHandler->open($transactionId, $context);
        }
    }

    private function getPaymentState(Payment $response): string
    {
        $intent = $response->getIntent();
        $relatedResource = $response->getTransactions()[0]->getRelatedResources()[0];
        $paymentState = '';

        switch ($intent) {
            case PaymentIntent::SALE:
                $sale = $relatedResource->getSale();
                if ($sale !== null) {
                    $paymentState = $sale->getState();
                }
                break;
            case PaymentIntent::AUTHORIZE:
                $authorization = $relatedResource->getAuthorization();
                if ($authorization !== null) {
                    $paymentState = $authorization->getState();
                }
                break;
            case PaymentIntent::ORDER:
                $order = $relatedResource->getOrder();
                if ($order !== null) {
                    $paymentState = $order->getState();
                }
                break;
        }

        return $paymentState;
    }
}
