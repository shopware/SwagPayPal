<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Context;

/**
 * @deprecated tag:v6.0.0 - will be removed and not extendable anymore
 */
interface MerchantIntegrationsServiceInterface
{
    public function fetchMerchantIntegrations(Context $context, ?string $salesChannelId = null): array;
}
