<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
interface CredentialsUtilInterface
{
    public function isSandbox(?string $salesChannelId = null): bool;

    public function getClientId(?string $salesChannelId = null): string;

    public function getMerchantPayerId(?string $salesChannelId = null): string;

    public function getBaseUrl(?string $salesChannelId = null): string;
}
