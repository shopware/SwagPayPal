<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class ExpressPrepareCheckoutRoute extends AbstractExpressPrepareCheckoutRoute
{
    public const EXPRESS_CHECKOUT_ACTIVE = 'payPalExpressCheckoutActive';
    public const PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID = 'payPalEcsCartData';

    /**
     * @var RegisterRoute
     */
    private $registerRoute;

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
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var SalesChannelContextSwitcher
     */
    private $salesChannelContextSwitcher;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        RegisterRoute $registerRoute,
        EntityRepositoryInterface $countryRepo,
        EntityRepositoryInterface $salutationRepo,
        AccountService $accountService,
        SalesChannelContextFactory $salesChannelContextFactory,
        PaymentMethodUtil $paymentMethodUtil,
        SalesChannelContextSwitcher $salesChannelContextSwitcher,
        OrderResource $orderResource,
        CartService $cartService,
        SystemConfigService $systemConfigService
    ) {
        $this->registerRoute = $registerRoute;
        $this->countryRepo = $countryRepo;
        $this->salutationRepo = $salutationRepo;
        $this->accountService = $accountService;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->salesChannelContextSwitcher = $salesChannelContextSwitcher;
        $this->orderResource = $orderResource;
        $this->cartService = $cartService;
        $this->systemConfigService = $systemConfigService;
    }

    public function getDecorated(): AbstractExpressPrepareCheckoutRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @OA\Post(
     *     path="/store-api/v{version}/paypal/express/prepare-checkout",
     *     description="Loggs in a guest customer, with the data of a paypal order",
     *     operationId="preparePayPalExpressCheckout",
     *     tags={"Store API", "PayPal"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="token", description="ID of the paypal order", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="The new context token"
     *    )
     * )
     *
     * @Route(
     *     "/store-api/v{version}/paypal/express/prepare-checkout",
     *     name="store-api.paypal.express.prepare_checkout",
     *     methods={"POST"}
     * )
     */
    public function prepareCheckout(SalesChannelContext $salesChannelContext, Request $request): ContextTokenResponse
    {
        $paypalOrderId = $request->request->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN);
        $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannel()->getId());

        $paypalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext());

        //Create and login a new guest customer
        $salesChannelContext->getContext()->addExtension(self::EXPRESS_CHECKOUT_ACTIVE, new ArrayStruct());
        $customerDataBag = $this->getCustomerDataBag($paypalOrder, $salesChannelContext);
        $this->registerRoute->register($customerDataBag, $salesChannelContext, false);
        $newContextToken = $this->accountService->login($customerDataBag->get('email'), $salesChannelContext, true);
        $salesChannelContext->getContext()->removeExtension(self::EXPRESS_CHECKOUT_ACTIVE);

        // Since a new customer was logged in, the context changed in the system,
        // but this doesn't effect the current context given as parameter.
        // Because of that a new context for the following operations is created
        $newSalesChannelContext = $this->salesChannelContextFactory->create(
            $newContextToken,
            $salesChannelContext->getSalesChannel()->getId()
        );

        // Set the payment method to PayPal
        $salesChannelDataBag = new DataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paypalPaymentMethodId,
        ]);
        $this->salesChannelContextSwitcher->update($salesChannelDataBag, $newSalesChannelContext);

        $cart = $this->cartService->getCart($newSalesChannelContext->getToken(), $salesChannelContext);

        $expressCheckoutData = new ExpressCheckoutData($paypalOrderId);
        $cart->addExtension(self::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID, $expressCheckoutData);

        // The cart needs to be saved.
        $this->cartService->recalculate($cart, $newSalesChannelContext);

        return new ContextTokenResponse($cart->getToken());
    }

    private function getCustomerDataBag(Order $paypalOrder, SalesChannelContext $salesChannelContext): RequestDataBag
    {
        $context = $salesChannelContext->getContext();
        $payer = $paypalOrder->getPayer();
        $address = $payer->getAddress();
        if ($address->getAddressLine1() === null && $address->getPostalCode() === null) {
            $address = $paypalOrder->getPurchaseUnits()[0]->getShipping()->getAddress();
        }

        $firstName = $payer->getName()->getGivenName();
        $lastName = $payer->getName()->getSurname();
        $salutationId = $this->getSalutationId($context);

        $countryId = null;
        $countryStateId = null;

        $countryCode = $address->getCountryCode();
        $country = $this->getCountryByCode($countryCode, $context);
        if ($country !== null) {
            $countryId = $country->getId();
            $countryStateId = $this->getCountryStateId(
                $address->getAdminArea1(),
                $country->getStates(),
                $countryCode
            );
        }
        $phone = $payer->getPhone();

        return new RequestDataBag([
            'guest' => true,
            'storefrontUrl' => $this->getStorefrontUrl($salesChannelContext),
            'salutationId' => $salutationId,
            'email' => $payer->getEmailAddress(),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'billingAddress' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'salutationId' => $salutationId,
                'street' => $address->getAddressLine1(),
                'zipcode' => $address->getPostalCode(),
                'countryId' => $countryId,
                'countryStateId' => $countryStateId,
                'phoneNumber' => $phone !== null ? $phone->getPhoneNumber()->getNationalNumber() : null,
                'city' => $address->getAdminArea2(),
                'additionalAddressLine1' => $address->getAddressLine2(),
            ],
        ]);
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salutationKey', 'not_specified')
        );

        /** @var SalutationEntity|null $salutation */
        $salutation = $this->salutationRepo->search($criteria, $context)->first();

        if ($salutation === null) {
            throw new \RuntimeException('No salutation found in Shopware');
        }

        return $salutation->getId();
    }

    private function getCountryByCode(string $code, Context $context): ?CountryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $code));
        $criteria->addAssociation('states');
        /** @var CountryEntity|null $country */
        $country = $this->countryRepo->search($criteria, $context)->first();

        return $country;
    }

    private function getCountryStateId(
        ?string $payPalCountryStateCode,
        ?CountryStateCollection $countryStates,
        string $countryCode
    ): ?string {
        if ($payPalCountryStateCode === null) {
            return null;
        }

        if ($countryStates === null || \count($countryStates) === 0) {
            return null;
        }

        /** @var CountryStateCollection $filteredCountryStates */
        $filteredCountryStates = $countryStates->filterAndReduceByProperty(
            'shortCode',
            \sprintf('%s-%s', $countryCode, $payPalCountryStateCode)
        );
        $countryState = $filteredCountryStates->first();
        if ($countryState === null) {
            return null;
        }

        return $countryState->getId();
    }

    private function getStorefrontUrl(SalesChannelContext $salesChannelContext): string
    {
        $salesChannel = $salesChannelContext->getSalesChannel();
        $domainUrl = $this->systemConfigService->get('core.loginRegistration.doubleOptInDomain', $salesChannel->getId());

        if (\is_string($domainUrl) && $domainUrl !== '') {
            return $domainUrl;
        }

        $domains = $salesChannel->getDomains();
        if ($domains === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        $domain = $domains->first();
        if ($domain === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        return $domain->getUrl();
    }
}
