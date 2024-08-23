<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Storefront\Data\Struct\ACDCCheckoutData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class ACDCCheckoutDataService extends AbstractCheckoutDataService
{
    /**
     * @internal
     */
    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        LocaleCodeProvider $localeCodeProvider,
        private readonly RouterInterface $router,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        private readonly TokenResourceInterface $tokenResource,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($paymentMethodDataRegistry, $localeCodeProvider, $router, $systemConfigService, $credentialsUtil);
    }

    public function buildCheckoutData(
        SalesChannelContext $context,
        ?Cart $cart = null,
        ?OrderEntity $order = null
    ): ?ACDCCheckoutData {
        //$token = $this->requestStack->getCurrentRequest()?->getSession()->get(FastlaneDataService::FASTLANE_SESSION_TOKEN);
        // todo: invalidate token!
        //if (!$token) {
            $token = $this->tokenResource->getSdkClientToken(
                $context->getSalesChannelId(),
                $this->getDomains($context),
            );
        //}

        return (new ACDCCheckoutData())->assign(
            [
                ...$this->getBaseData($context, $order),
                'sdkClientToken' => $token->getAccessToken(),
                'billingAddress' => $this->getBillingAddress($order, $context),
                'billingAddressId' => $context->getCustomer()?->getActiveBillingAddress()?->getId(),
                'shippingAddressId' => $context->getCustomer()?->getActiveShippingAddress()?->getId(),
                'modifyAddressUrl' => $this->router->generate('frontend.paypal.fastlane.modify_address'),
                'customerEmail' => $context->getCustomer()?->getEmail(),
            ]
        );
    }

    /**
     * @return array<string>
     */
    private function getDomains(SalesChannelContext $salesChannelContext): array
    {
        $urls = $salesChannelContext->getSalesChannel()->getDomains()?->map(fn (SalesChannelDomainEntity $domain): string => $domain->getUrl());

        $hosts = [];
        foreach ($urls ?? [] as $url) {
            $hosts[] = parse_url($url, PHP_URL_HOST);
        }

        return array_unique($hosts);
    }

    private function getBillingAddress(?OrderEntity $order, SalesChannelContext $context): array
    {
        $address = $order?->getBillingAddress() ?? $context->getCustomer()?->getActiveBillingAddress();

        return [
            'addressLine1' => $address?->getStreet(),
            //'addressLine2' => $address->getAdditionalAddressLine1(),
            'adminArea1' => 'CA',//$address->getCountryState()?->getShortCode(),
            'adminArea2' => $address->getCity(),
            'postalCode' => $address->getZipcode(),
            'countryCode' => $address->getCountry()?->getIso(),
            //'phone' => ['nationalNumber' => $address->getPhoneNumber()],
        ];
    }

    public function getMethodDataClass(): string
    {
        return ACDCMethodData::class;
    }
}
