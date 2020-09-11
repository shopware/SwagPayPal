<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Plus\PlusData;
use Swag\PayPal\PaymentsApi\Builder\CartPaymentBuilderInterface;
use Swag\PayPal\PaymentsApi\Builder\OrderPaymentBuilderInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Swag\PayPal\Util\PaymentTokenExtractor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PlusDataService
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CartPaymentBuilderInterface
     */
    private $cartPaymentBuilder;

    /**
     * @var OrderPaymentBuilderInterface
     */
    private $orderPaymentBuilder;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var LocaleCodeProvider
     */
    private $localeCodeProvider;

    public function __construct(
        CartPaymentBuilderInterface $cartPaymentBuilder,
        OrderPaymentBuilderInterface $orderPaymentBuilder,
        PaymentResource $paymentResource,
        RouterInterface $router,
        PaymentMethodUtil $paymentMethodUtil,
        LocaleCodeProvider $localeCodeProvider
    ) {
        $this->cartPaymentBuilder = $cartPaymentBuilder;
        $this->orderPaymentBuilder = $orderPaymentBuilder;
        $this->paymentResource = $paymentResource;
        $this->router = $router;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->localeCodeProvider = $localeCodeProvider;
    }

    public function getPlusData(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        SwagPayPalSettingStruct $settings
    ): ?PlusData {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            return null;
        }

        $finishUrl = $this->createFinishUrl();
        $payment = $this->cartPaymentBuilder->getPayment($cart, $salesChannelContext, $finishUrl, false);

        return $this->getPlusDataFromPayment($payment, $salesChannelContext, $customer, $settings->getSandbox());
    }

    public function getPlusDataFromOrder(
        OrderEntity $order,
        SalesChannelContext $salesChannelContext,
        SwagPayPalSettingStruct $settings
    ): ?PlusData {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            return null;
        }

        $transactions = $order->getTransactions();
        if ($transactions === null) {
            throw new InvalidOrderException($order->getId());
        }

        $firstTransaction = $transactions->first();
        if ($firstTransaction === null) {
            throw new InvalidOrderException($order->getId());
        }

        $finishUrl = $this->createFinishUrl(true);
        $paymentTransaction = new AsyncPaymentTransactionStruct($firstTransaction, $order, $finishUrl);
        $payment = $this->orderPaymentBuilder->getPayment($paymentTransaction, $salesChannelContext);

        $plusData = $this->getPlusDataFromPayment($payment, $salesChannelContext, $customer, $settings->getSandbox());
        if ($plusData === null) {
            return null;
        }

        $plusData->setOrderId($order->getId());

        return $plusData;
    }

    private function createFinishUrl(bool $orderUpdate = false): string
    {
        return $this->router->generate(
            'payment.paypal.plus.finalize.transaction',
            [
                PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER => true,
                'changedPayment' => $orderUpdate,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function getPlusDataFromPayment(
        Payment $payment,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer,
        bool $useSandbox
    ): ?PlusData {
        $payment->setIntent(PaymentIntentV1::SALE);

        try {
            $response = $this->paymentResource->create(
                $payment,
                $salesChannelContext->getSalesChannel()->getId(),
                PartnerAttributionId::PAYPAL_PLUS
            );
        } catch (\Exception $e) {
            return null;
        }

        $context = $salesChannelContext->getContext();
        $payPalData = new PlusData();
        $payPalData->assign([
            'approvalUrl' => $response->getLinks()[1]->getHref(),
            'mode' => $useSandbox ? 'sandbox' : 'live',
            'customerSelectedLanguage' => $this->getPaymentWallLanguage($context),
            'paymentMethodId' => $this->paymentMethodUtil->getPayPalPaymentMethodId($context),
            'paypalPaymentId' => $response->getId(),
            'paypalToken' => PaymentTokenExtractor::extract($response),
            'checkoutOrderUrl' => $this->router->generate('sales-channel-api.checkout.order.create', ['version' => PlatformRequest::API_VERSION]),
            'setPaymentRouteUrl' => $this->router->generate('store-api.order.set-payment', ['version' => PlatformRequest::API_VERSION]),
            'contextSwitchUrl' => $this->router->generate('store-api.switch-context', ['version' => PlatformRequest::API_VERSION]),
            'isEnabledParameterName' => PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID,
            'languageId' => $salesChannelContext->getContext()->getLanguageId(),
        ]);
        $billingAddress = $customer->getDefaultBillingAddress();
        if ($billingAddress !== null) {
            $country = $billingAddress->getCountry();
            if ($country !== null) {
                $payPalData->assign(['customerCountryIso' => $country->getIso()]);
            }
        }

        return $payPalData;
    }

    private function getPaymentWallLanguage(Context $context): string
    {
        $languageIso = $this->localeCodeProvider->getLocaleCodeFromContext($context);

        $plusLanguage = 'en_GB';
        // use english as default, use german if the locale is from german speaking country (de_DE, de_AT, etc)
        // by now the PPP iFrame does not support other languages
        if (\strncmp($languageIso, 'de-', 3) === 0) {
            $plusLanguage = 'de_DE';
        }

        return $plusLanguage;
    }
}
