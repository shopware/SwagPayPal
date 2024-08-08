<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Address;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Name;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\PhoneNumber;
use Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class FastlanePrepareCheckoutRoute
{
    public const FASTLANE_CART_EXTENSION_ID = 'payPalFastlaneCartData';

    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractRegisterRoute $registerRoute,
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly SystemConfigService $systemConfigService,
        private readonly PaymentMethodDataRegistry $methodDataRegistry,
        private readonly CartService $cartService,
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $countryRepository,
        private readonly EntityRepository $countryStateRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getDecorated(): self
    {
        throw new DecorationPatternException(self::class);
    }

    #[OA\Post(
        path: '/store-api/paypal/express/prepare-checkout',
        operationId: 'preparePayPalExpressCheckout',
        description: 'Logs in a guest customer, with the data of a paypal order',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(
            property: 'token',
            description: 'ID of the paypal order',
            type: 'string'
        )])),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'The url to redirect to',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'redirectUrl',
                type: 'string'
            )])
        )],
    )]
    #[Route(path: '/store-api/paypal/fastlane/prepare-checkout', name: 'store-api.paypal.fastlane.prepare_checkout', methods: ['POST'])]
    public function prepareCheckout(SalesChannelContext $salesChannelContext, Request $request): ContextTokenResponse
    {
        $profileData = (new ProfileData())->assign($request->request->all('profileData'));
        $email = $request->request->getString('email');
        if (!$email) {
            throw new InvalidParameterException('email');
        }

        $customerDataBag = $this->getRegisterCustomerDataBag($email, $profileData, $salesChannelContext);
        $response = $this->registerRoute->register($customerDataBag, $salesChannelContext, false);
        $this->logger->debug('Customer created and logged in');

        $newToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);

        if ($newToken === null || $newToken === '') {
            throw RoutingException::missingRequestParameter(PlatformRequest::HEADER_CONTEXT_TOKEN);
        }

        $salesChannelContext = $this->salesChannelContextFactory->create($newToken, $salesChannelContext->getSalesChannel()->getId());
        $this->contextSwitchRoute->switchContext(new RequestDataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $this->methodDataRegistry->getEntityIdFromData(
                $this->methodDataRegistry->getPaymentMethod(ACDCMethodData::class),
                $salesChannelContext->getContext()
            ),
        ]), $salesChannelContext);

        $cart = $this->cartService->getCart($newToken, $salesChannelContext);
        $cart->addExtension(self::FASTLANE_CART_EXTENSION_ID, $profileData);
        $this->cartService->recalculate($cart, $salesChannelContext);

        return new ContextTokenResponse($newToken);
    }

    private function getRegisterCustomerDataBag(string $email, ProfileData $profileData, SalesChannelContext $salesChannelContext): RequestDataBag
    {
        $salutationId = $this->getSalutationId($salesChannelContext->getContext());

        $card = $profileData->getCard()->getPaymentSource()->getCard();

        $data = new RequestDataBag([
            'guest' => true,
            'storefrontUrl' => $this->getStorefrontUrl($salesChannelContext),
            'salutationId' => $salutationId,
            'email' => $email,
            'firstName' => $profileData->getName()->getFirstName(),
            'lastName' => $profileData->getName()->getLastName(),
            'billingAddress' => $this->getAddressData($card->getBillingAddress(), $profileData->getName(), null, $salesChannelContext->getContext(), $salutationId),
            'shippingAddress' => $this->getAddressData($profileData->getShippingAddress()->getAddress(), $profileData->getShippingAddress()->getName(), $profileData->getShippingAddress()->getPhoneNumber(), $salesChannelContext->getContext(), $salutationId),
            'acceptedDataProtection' => true,
        ]);

        return $data;
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('salutationKey', 'not_specified'));

        $salutationId = $this->salutationRepository->searchIds($criteria, $context)->firstId();

        if ($salutationId !== null) {
            return $salutationId;
        }

        $salutationId = $this->salutationRepository->searchIds($criteria->resetFilters(), $context)->firstId();

        if ($salutationId === null) {
            throw new \RuntimeException('No salutation found in Shopware');
        }

        return $salutationId;
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

    /**
     * @return array<string, string|null>
     */
    private function getAddressData(Address $address, Name $name, ?PhoneNumber $phoneNumber, Context $context, ?string $salutationId = null): array
    {
        $countryCode = $address->getCountryCode();
        $countryId = $this->getCountryId($countryCode, $context);
        $countryStateId = $this->getCountryStateId($countryId, $countryCode, $address->getAdminArea1(), $context);

        return [
            'firstName' => $name->getFirstName(),
            'lastName' => $name->getLastName(),
            'salutationId' => $salutationId,
            'street' => $address->getAddressLine1(),
            'zipcode' => $address->getPostalCode(),
            'countryId' => $countryId,
            'countryStateId' => $countryStateId,
            'phoneNumber' => $phoneNumber?->getNationalNumber(),
            'city' => $address->getAdminArea2(),
            'additionalAddressLine1' => $address->getAddressLine2(),
        ];
    }

    private function getCountryId(string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $code));

        return $this->countryRepository->searchIds($criteria, $context)->firstId();
    }

    private function getCountryStateId(?string $countryId, string $countryCode, ?string $stateCode, Context $context): ?string
    {
        if ($countryId === null) {
            return null;
        }

        if ($stateCode === null || $stateCode === '') {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('countryId', $countryId));
        $criteria->addFilter(new EqualsFilter('shortCode', \sprintf('%s-%s', $countryCode, $stateCode)));

        return $this->countryStateRepository->searchIds($criteria, $context)->firstId();
    }
}
