<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Exception\StateMachineNotFoundException;
use Shopware\Core\System\StateMachine\Exception\StateMachineStateNotFoundException;
use Swag\PayPal\Payment\Exception\CurrencyNotFoundException;
use Swag\PayPal\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Payment\Handler\PayPalHandler;
use Swag\PayPal\Payment\Handler\PlusHandler;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\PayPal\PaymentStatus;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    public const PAYPAL_REQUEST_PARAMETER_PAYER_ID = 'PayerID';
    public const PAYPAL_REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    /**
     * @var EcsSpbHandler
     */
    private $ecsSpbHandler;

    /**
     * @var PayPalHandler
     */
    private $payPalHandler;

    /**
     * @var PlusHandler
     */
    private $plusHandler;

    public function __construct(
        PaymentResource $paymentResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        EcsSpbHandler $ecsSpbHandler,
        PayPalHandler $payPalHandler,
        PlusHandler $plusHandler
    ) {
        $this->paymentResource = $paymentResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->ecsSpbHandler = $ecsSpbHandler;
        $this->payPalHandler = $payPalHandler;
        $this->plusHandler = $plusHandler;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                (new CustomerNotLoggedInException())->getMessage()
            );
        }

        if ($dataBag->get('isPayPalExpressCheckout')) {
            try {
                return $this->ecsSpbHandler->handleEcsPayment($transaction, $dataBag, $salesChannelContext, $customer);
            } catch (AddressNotFoundException | CurrencyNotFoundException | InconsistentCriteriaIdsException
            | InvalidOrderException | PayPalSettingsInvalidException $e
            ) {
                throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $e->getMessage());
            }
        }

        if ($dataBag->get('isPayPalSpbCheckout')) {
            return $this->ecsSpbHandler->handleSpbPayment($transaction, $dataBag, $salesChannelContext);
        }

        if ($dataBag->getBoolean('isPayPalPlusEnabled')) {
            try {
                return $this->plusHandler->handlePlusPayment($transaction, $dataBag, $salesChannelContext, $customer);
            } catch (AddressNotFoundException $e) {
                throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $e->getMessage());
            }
        }

        try {
            $response = $this->payPalHandler->handlePayPalPayment($transaction, $salesChannelContext, $customer);
        } catch (AddressNotFoundException | CurrencyNotFoundException | InvalidOrderException
            | InconsistentCriteriaIdsException | PayPalSettingsInvalidException $e
        ) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $e->getMessage());
        }

        return new RedirectResponse($response->getLinks()[1]->getHref());
    }

    /**
     * @throws AsyncPaymentFinalizeException
     * @throws CustomerCanceledAsyncPaymentException
     * @throws InconsistentCriteriaIdsException
     * @throws StateMachineNotFoundException
     * @throws StateMachineStateNotFoundException
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
        $isExpressCheckout = $request->query->getBoolean('isPayPalExpressCheckout');
        $isSPBCheckout = $request->query->getBoolean('isPayPalSpbCheckout');
        $isPlus = $request->query->getBoolean('isPayPalPlus');
        $partnerAttributionId = $this->getPartnerAttributionId($isExpressCheckout, $isSPBCheckout, $isPlus);

        try {
            $response = $this->paymentResource->execute(
                $payerId,
                $paymentId,
                $salesChannelContext->getSalesChannel()->getId(),
                $partnerAttributionId
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentFinalizeException(
                $transactionId,
                'An error occurred during the communication with PayPal' . PHP_EOL . $e->getMessage()
            );
        }

        $paymentState = $this->getPaymentState($response);
        $context = $salesChannelContext->getContext();

        // apply the payment status if its completed by PayPal
        if ($paymentState === PaymentStatus::PAYMENT_COMPLETED) {
            $this->orderTransactionStateHandler->pay($transactionId, $context);
        } else {
            $this->orderTransactionStateHandler->open($transactionId, $context);
        }
    }

    private function getPartnerAttributionId(bool $isECS, bool $isSPB, bool $isPlus): string
    {
        if ($isECS) {
            return PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT;
        }

        if ($isSPB) {
            return PartnerAttributionId::SMART_PAYMENT_BUTTONS;
        }

        if ($isPlus) {
            return PartnerAttributionId::PAYPAL_PLUS;
        }

        return PartnerAttributionId::PAYPAL_CLASSIC;
    }

    private function getPaymentState(Payment $payment): string
    {
        $intent = $payment->getIntent();
        $relatedResource = $payment->getTransactions()[0]->getRelatedResources()[0];
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
