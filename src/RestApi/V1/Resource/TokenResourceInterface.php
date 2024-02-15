<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Token;

#[Package('checkout')]
interface TokenResourceInterface
{
    public function getToken(?string $salesChannelId): Token;

    public function getUserIdToken(?string $salesChannelId, ?string $targetCustomerId = null): Token;
}
