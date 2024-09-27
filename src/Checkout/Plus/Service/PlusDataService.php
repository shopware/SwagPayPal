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
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Plus\PlusData;
use Swag\PayPal\PaymentsApi\Builder\CartPaymentBuilderInterface;
use Swag\PayPal\PaymentsApi\Builder\OrderPaymentBuilderInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Swag\PayPal\Util\PaymentTokenExtractor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement.
 */
#[Package('checkout')]
class PlusDataService
{
    private RouterInterface $router;

    private CartPaymentBuilderInterface $cartPaymentBuilder;

    private OrderPaymentBuilderInterface $orderPaymentBuilder;

    private PaymentResource $paymentResource;

    private PaymentMethodUtil $paymentMethodUtil;

    private LocaleCodeProvider $localeCodeProvider;

    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(
        CartPaymentBuilderInterface $cartPaymentBuilder,
        OrderPaymentBuilderInterface $orderPaymentBuilder,
        PaymentResource $paymentResource,
        RouterInterface $router,
        PaymentMethodUtil $paymentMethodUtil,
        LocaleCodeProvider $localeCodeProvider,
        SystemConfigService $systemConfigService,
    ) {
        $this->cartPaymentBuilder = $cartPaymentBuilder;
        $this->orderPaymentBuilder = $orderPaymentBuilder;
        $this->paymentResource = $paymentResource;
        $this->router = $router;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->systemConfigService = $systemConfigService;
    }

    public function getPlusData(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
    ): ?PlusData {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            return null;
        }

        $finishUrl = $this->createFinishUrl();
        $payment = $this->cartPaymentBuilder->getPayment($cart, $salesChannelContext, $finishUrl, false);

        return $this->getPlusDataFromPayment($payment, $salesChannelContext, $customer);
    }

    public function getPlusDataFromOrder(
        OrderEntity $order,
        SalesChannelContext $salesChannelContext,
    ): ?PlusData {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            return null;
        }

        $transactions = $order->getTransactions();
        if ($transactions === null) {
            throw PaymentException::invalidOrder($order->getId());
        }

        $firstTransaction = $transactions->first();
        if ($firstTransaction === null) {
            throw PaymentException::invalidOrder($order->getId());
        }

        $finishUrl = $this->createFinishUrl(true);
        $paymentTransaction = new AsyncPaymentTransactionStruct($firstTransaction, $order, $finishUrl);
        $payment = $this->orderPaymentBuilder->getPayment($paymentTransaction, $salesChannelContext);

        $plusData = $this->getPlusDataFromPayment($payment, $salesChannelContext, $customer);
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
    ): ?PlusData {
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
            'approvalUrl' => $response->getLinks()->getAt(1)?->getHref(),
            'mode' => $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelContext->getSalesChannelId()) ? 'sandbox' : 'live',
            'customerSelectedLanguage' => $this->getPaymentWallLanguage($context),
            'paymentMethodId' => $this->paymentMethodUtil->getPayPalPaymentMethodId($context),
            'paypalPaymentId' => $response->getId(),
            'paypalToken' => PaymentTokenExtractor::extract($response),
            'handlePaymentUrl' => $this->router->generate('frontend.paypal.plus.handle'),
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
