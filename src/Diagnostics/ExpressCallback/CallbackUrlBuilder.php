<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\StoreApiUrlStorage;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CallbackUrlBuilder
{
    private const STOREFRONT_ROUTE = 'frontend.paypal.express.shipping_callback';

    private const STORE_API_ROUTE = 'store-api.paypal.express.shipping_callback';

    public function __construct(
        private readonly RouterInterface $router,
        private readonly StoreApiUrlStorage $storeApiUrlStorage,
        private readonly TrustedHosts $trustedHosts,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getCallbackUrls(SalesChannelEntity $salesChannel, string $token, Context $context): array
    {
        $storefrontPath = $this->getPath(self::STOREFRONT_ROUTE, $salesChannel->getId(), $token);
        if ($salesChannel->getTypeId() === Defaults::SALES_CHANNEL_TYPE_STOREFRONT && $storefrontPath !== null) {
            $urls = [];
            foreach ($salesChannel->getDomains() ?? [] as $domain) {
                $urls[] = \rtrim($domain->getUrl(), '/') . $storefrontPath;
            }

            return \array_values(\array_unique($urls));
        }

        $storeApiPath = $this->getPath(self::STORE_API_ROUTE, $salesChannel->getId(), $token);
        $storeApiUrl = $this->storeApiUrlStorage->get($salesChannel->getId());
        if ($storeApiUrl === null || $storeApiPath === null || !$this->trustedHosts->contains($storeApiUrl, $context)) {
            return [];
        }

        return [$storeApiUrl . $storeApiPath];
    }

    /**
     * From the route, as generating the URL would add the Administration's base path.
     */
    private function getPath(string $route, string $salesChannelId, string $token): ?string
    {
        $path = $this->router->getRouteCollection()->get($route)?->getPath();
        if ($path === null) {
            return null;
        }

        return \strtr($path, ['{salesChannelId}' => $salesChannelId, '{token}' => $token]);
    }
}
