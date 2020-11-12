<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Handler\PlusPuiHandler;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    public const PAYPAL_REQUEST_PARAMETER_PAYER_ID = 'PayerID';
    public const PAYPAL_REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';
    public const PAYPAL_REQUEST_PARAMETER_TOKEN = 'token';
    public const PAYPAL_EXPRESS_CHECKOUT_ID = 'isPayPalExpressCheckout';
    public const PAYPAL_SMART_PAYMENT_BUTTONS_ID = 'isPayPalSpbCheckout';
    public const PAYPAL_PLUS_CHECKOUT_ID = 'isPayPalPlusCheckout';
    public const PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER = 'isPayPalPlus';

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
     * @var PlusPuiHandler
     */
    private $plusPuiHandler;

    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        EcsSpbHandler $ecsSpbHandler,
        PayPalHandler $payPalHandler,
        PlusPuiHandler $plusPuiHandler
    ) {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->ecsSpbHandler = $ecsSpbHandler;
        $this->payPalHandler = $payPalHandler;
        $this->plusPuiHandler = $plusPuiHandler;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new AsyncPaymentProcessException(
                $transactionId,
                (new CustomerNotLoggedInException())->getMessage()
            );
        }

        $this->orderTransactionStateHandler->process($transactionId, $salesChannelContext->getContext());
        if ($dataBag->get(self::PAYPAL_EXPRESS_CHECKOUT_ID)) {
            try {
                return $this->ecsSpbHandler->handleEcsPayment($transaction, $dataBag, $salesChannelContext, $customer);
            } catch (\Exception $e) {
                throw new AsyncPaymentProcessException($transactionId, $e->getMessage());
            }
        }

        if ($dataBag->get(self::PAYPAL_SMART_PAYMENT_BUTTONS_ID)) {
            return $this->ecsSpbHandler->handleSpbPayment($transaction, $dataBag, $salesChannelContext);
        }

        if ($dataBag->getBoolean(self::PAYPAL_PLUS_CHECKOUT_ID)) {
            try {
                return $this->plusPuiHandler->handlePlusPayment($transaction, $dataBag, $salesChannelContext, $customer);
            } catch (\Exception $e) {
                throw new AsyncPaymentProcessException($transactionId, $e->getMessage());
            }
        }

        try {
            $response = $this->payPalHandler->handlePayPalOrder($transaction, $salesChannelContext, $customer);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException($transactionId, $e->getMessage());
        }

        foreach ($response->getLinks() as $link) {
            if ($link->getRel() !== Link::RELATION_APPROVE) {
                continue;
            }

            return new RedirectResponse($link->getHref());
        }

        throw new AsyncPaymentProcessException($transactionId, 'No approve link provided by PayPal');
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
        if ($request->query->getBoolean('cancel')) {
            throw new CustomerCanceledAsyncPaymentException(
                $transaction->getOrderTransaction()->getId(),
                'Customer canceled the payment on the PayPal page'
            );
        }

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $context = $salesChannelContext->getContext();

        $payerId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
        $paymentId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);
        $token = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_TOKEN);

        $isExpressCheckout = $request->query->getBoolean(self::PAYPAL_EXPRESS_CHECKOUT_ID);
        $isSPBCheckout = $request->query->getBoolean(self::PAYPAL_SMART_PAYMENT_BUTTONS_ID);
        $isPlus = $request->query->getBoolean(self::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER);

        $partnerAttributionId = $this->getPartnerAttributionId($isExpressCheckout, $isSPBCheckout, $isPlus);
        $orderDataPatchNeeded = $isExpressCheckout || $isSPBCheckout || $isPlus;

        if ($paymentId) {
            $this->plusPuiHandler->handleFinalizePayment(
                $transaction,
                $salesChannelId,
                $context,
                $paymentId,
                $payerId,
                $partnerAttributionId,
                $orderDataPatchNeeded
            );

            return;
        }

        $this->payPalHandler->handleFinalizeOrder(
            $transaction,
            $token,
            $salesChannelId,
            $context,
            $partnerAttributionId,
            $orderDataPatchNeeded
        );
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
}
