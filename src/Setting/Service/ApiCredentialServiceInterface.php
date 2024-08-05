<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Log\Package;

/**
 * @deprecated tag:v10.0.0 - interface will be removed, use `ApiCredentialService` instead
 */
#[Package('checkout')]
interface ApiCredentialServiceInterface
{
    public function testApiCredentials(string $clientId, string $clientSecret, bool $sandboxActive): bool;

    public function getApiCredentials(string $authCode, string $sharedId, string $nonce, bool $sandboxActive): array;
}
