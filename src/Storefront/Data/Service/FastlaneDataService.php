<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Routing\Router;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Service\AbstractScriptDataService;
use Swag\PayPal\Storefront\Data\Struct\FastlaneData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class FastlaneDataService extends AbstractScriptDataService
{
    /**
     * @internal
     */
    public function __construct(
        LocaleCodeProvider $localeCodeProvider,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        private readonly TokenResourceInterface $tokenResource,
        private readonly RouterInterface $router,
    ) {
        parent::__construct($localeCodeProvider, $systemConfigService, $credentialsUtil);
    }

    public function buildFastlaneData(
        SalesChannelContext $salesChannelContext,
    ): ?FastlaneData {
        return (new FastlaneData())->assign([
            ...parent::getBaseData($salesChannelContext),
            'sdkClientToken' => $this->tokenResource->getSdkClientToken(
                $salesChannelContext->getSalesChannelId(),
                $this->getDomains($salesChannelContext),
            )->getAccessToken(),
            'prepareCheckoutUrl' => $this->router->generate('frontend.paypal.fastlane.prepare_checkout'),
            'checkoutConfirmUrl' => $this->router->generate('frontend.checkout.confirm.page'),
        ]);
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
}
