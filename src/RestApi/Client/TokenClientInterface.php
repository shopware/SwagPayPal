<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
interface TokenClientInterface
{
    /**
     * @param array<string, string> $additionalData
     */
    public function getToken(array $additionalData = []): array;
}
