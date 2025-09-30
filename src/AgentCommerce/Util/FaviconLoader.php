<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Storefront\Theme\AbstractResolvedConfigLoader;
use Shopware\Storefront\Theme\ConfigLoader\AbstractAvailableThemeProvider;
use Swag\PayPal\AgentCommerce\Exception\HoneyWebhookException;

/**
 * @internal
 */
#[Package('checkout')]
class FaviconLoader
{
    public function __construct(
        private readonly AbstractAvailableThemeProvider $themeLoader,
        private readonly AbstractResolvedConfigLoader $configService,
        private readonly SalesChannelContextService $contextService
    ) {
    }

    public function loadFaviconLink(string $salesChannelId, Context $context): string
    {
        $themeId = $this->themeLoader->load($context, true)[$salesChannelId] ?? null;
        if ($themeId === null) {
            throw HoneyWebhookException::storefrontSalesChannelNotFound();
        }

        $salesChannelContext = $this->contextService->get(new SalesChannelContextServiceParameters(
            salesChannelId: $salesChannelId,
            token: Random::getAlphanumericString(32),
            originalContext: $context
        ));

        $config = $this->configService->load($themeId, $salesChannelContext);

        return $config['sw-logo-favicon'] ?? '';
    }
}
