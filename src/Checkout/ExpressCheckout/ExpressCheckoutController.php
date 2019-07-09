<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Exception\BadCredentialsException;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountRegistrationService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Swag\PayPal\Payment\Builder\CartPaymentBuilderInterface;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Util\PaymentMethodUtil;
use Swag\PayPal\Util\PaymentTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ExpressCheckoutController extends AbstractController
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
     * @var AccountRegistrationService
     */
    private $accountRegistrationService;

    /**
     * @var EntityRepositoryInterface
     */
    private $countryRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepo;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var SalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(
        CartPaymentBuilderInterface $cartPaymentBuilder,
        CartService $cartService,
        AccountRegistrationService $accountRegistrationService,
        EntityRepositoryInterface $countryRepo,
        EntityRepositoryInterface $salutationRepo,
        AccountService $accountService,
        SalesChannelContextFactory $salesChannelContextFactory,
        PaymentResource $paymentResource,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->cartPaymentBuilder = $cartPaymentBuilder;
        $this->cartService = $cartService;
        $this->accountRegistrationService = $accountRegistrationService;
        $this->countryRepo = $countryRepo;
        $this->salutationRepo = $salutationRepo;
        $this->accountService = $accountService;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->paymentResource = $paymentResource;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @Route("/sales-channel-api/v{version}/_action/paypal/create-payment", name="sales-channel-api.action.paypal.create_payment", methods={"GET"})
     */
    public function createPayment(SalesChannelContext $context): JsonResponse
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $payment = $this->cartPaymentBuilder->getPayment(
            $cart,
            $context,
            'https://www.example.com/',
            true
        );
        $paymentResource = $this->paymentResource->create(
            $payment,
            $context->getSalesChannel()->getId(),
            PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT
        );

        return new JsonResponse([
            'token' => PaymentTokenExtractor::extract($paymentResource),
        ]);
    }

    /**
     * @Route("/paypal/approve-payment", name="paypal.approve_payment", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     *
     * @throws BadCredentialsException
     * @throws PayPalSettingsInvalidException
     */
    public function onApprove(SalesChannelContext $context, Request $request): JsonResponse
    {
        $paymentId = $request->request->get('paymentId');
        $payment = $this->paymentResource->get($paymentId, $context->getSalesChannel()->getId());
        $paypalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context->getContext());

        /**
         * Create and login a new guest customer
         */
        $customerDataBag = $this->getCustomerDataBagFromPayment($payment, $context->getContext());
        $customer = $this->accountRegistrationService->register($customerDataBag, true, $context);
        $newContextToken = $this->accountService->login($customerDataBag->get('email'), $context, true);

        /**
         * Since we logged in a new Customer the context changed in the system but this doesnt effect our context.
         * Because of that we need to create a new Context for the following operations
         */
        $newContext = $this->salesChannelContextFactory->create($newContextToken, $context->getSalesChannel()->getId());

        /**
         * Get the cart and add the PayPalExpress extension to it
         */
        $cart = $this->cartService->getCart($newContext->getToken(), $context);
        $expressCheckoutData = new ExpressCheckoutData();

        $expressCheckoutData->setIsExpressCheckout(true);
        $expressCheckoutData->setPaymentId($paymentId);
        $expressCheckoutData->setPayerId($payment->getPayer()->getPayerInfo()->getPayerId());

        if ($paypalPaymentMethodId) {
            $expressCheckoutData->setPaymentMethodId($paypalPaymentMethodId);
        }

        $cart->addExtension('expressCheckoutData', $expressCheckoutData);

        /*
         * The cart needs to be saved.
         */
        $this->cartService->recalculate($cart, $newContext);

        return new JsonResponse($customer);
    }

    private function getCustomerDataBagFromPayment(Payment $payment, Context $context): DataBag
    {
        /** @var Payment\Payer $payer */
        $payer = $payment->getPayer();
        $payerInfo = $payer->getPayerInfo();
        $billingAddress = $payerInfo->getBillingAddress() ?? $payerInfo->getShippingAddress();
        $firstName = $payerInfo->getFirstName();
        $lastName = $payerInfo->getLastName();
        $salutationId = $this->getSalutationId($context);

        return new DataBag([
            'salutationId' => $salutationId,
            'email' => $payerInfo->getEmail(),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'billingAddress' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'salutationId' => $salutationId,
                'street' => $billingAddress->getLine1(),
                'zipcode' => $billingAddress->getPostalCode(),
                'countryId' => $this->getCountryIdByCode($billingAddress->getCountryCode(), $context),
                'phone' => $billingAddress->getPhone(),
                'city' => $billingAddress->getCity(),
                'additionalAddressLine1' => $billingAddress->getLine2(),
            ],
        ]);
    }

    private function getCountryIdByCode(string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('iso', $code)
        );
        /** @var CountryEntity$country */
        $country = $this->countryRepo->search($criteria, $context)->first();

        if (!$country instanceof CountryEntity) {
            return null;
        }

        return $country->getId();
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salutationKey', 'not_specified')
        );

        /** @var SalutationEntity $salutation */
        $salutation = $this->salutationRepo->search($criteria, $context)->first();

        return $salutation->getId();
    }
}
