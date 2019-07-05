<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Builder\CartPaymentBuilderInterface;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Util\PaymentTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SPBCheckoutController extends AbstractController
{
    public const PAYPAL_SPB_PARAMETER_PAYMENT_ID = 'paymentId';
    public const PAYPAL_SPB_PARAMETER_PAYER_ID = 'payerId';

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
     * @Route("/sales-channel-api/v{version}/_action/paypal/spb/create-payment", name="sales-channel-api.action.paypal.spb.create_payment", methods={"GET"})
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

    /**
     * @Route("/sales-channel-api/v{version}/_action/paypal/spb/approve-payment", name="sales-channel-api.action.paypal.spb.approve_payment", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function onApprove(SalesChannelContext $salesChannelContext, Request $request): Response
    {
        $paymentId = $request->request->get(self::PAYPAL_SPB_PARAMETER_PAYMENT_ID);
        $payerId = $request->request->get(self::PAYPAL_SPB_PARAMETER_PAYER_ID);

        $checkoutData = new SPBCheckoutData($paymentId, $payerId);

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $cart->addExtension('spbCheckoutData', $checkoutData);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
