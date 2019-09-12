<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Builder\CartPaymentBuilderInterface;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Util\PaymentTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SPBCheckoutController extends AbstractController
{
    /**
     * @var CartPaymentBuilderInterface
     */
    private $cartPaymentBuilder;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var PayerInfoPatchBuilder
     */
    private $payerInfoPatchBuilder;

    /**
     * @var ShippingAddressPatchBuilder
     */
    private $shippingAddressPatchBuilder;

    public function __construct(
        CartPaymentBuilderInterface $cartPaymentBuilder,
        CartService $cartService,
        PaymentResource $paymentResource,
        PayerInfoPatchBuilder $payerInfoPatchBuilder,
        ShippingAddressPatchBuilder $shippingAddressPatchBuilder
    ) {
        $this->cartPaymentBuilder = $cartPaymentBuilder;
        $this->cartService = $cartService;
        $this->paymentResource = $paymentResource;
        $this->payerInfoPatchBuilder = $payerInfoPatchBuilder;
        $this->shippingAddressPatchBuilder = $shippingAddressPatchBuilder;
    }

    /**
     * @RouteScope(scopes={"sales-channel-api"})
     * @Route("/sales-channel-api/v{version}/_action/paypal/spb/create-payment", name="sales-channel-api.action.paypal.spb.create_payment", methods={"POST"})
     *
     * @throws AddressNotFoundException
     * @throws CustomerNotLoggedInException
     * @throws PayPalSettingsInvalidException
     */
    public function createPayment(SalesChannelContext $salesChannelContext): JsonResponse
    {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new CustomerNotLoggedInException();
        }

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $payment = $this->cartPaymentBuilder->getPayment(
            $cart,
            $salesChannelContext,
            'https://www.example.com/',
            false
        );
        $payment->getApplicationContext()->setUserAction(ApplicationContext::USER_ACTION_TYPE_CONTINUE);

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $response = $this->paymentResource->create(
            $payment,
            $salesChannelId,
            PartnerAttributionId::SMART_PAYMENT_BUTTONS
        );

        $patches = [
            $this->shippingAddressPatchBuilder->createShippingAddressPatch($customer),
            $this->payerInfoPatchBuilder->createPayerInfoPatch($customer),
        ];
        $this->paymentResource->patch($patches, $response->getId(), $salesChannelId);

        return new JsonResponse([
            'token' => PaymentTokenExtractor::extract($response),
        ]);
    }
}
