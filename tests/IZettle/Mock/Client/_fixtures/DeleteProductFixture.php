<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client\_fixtures;

use Swag\PayPal\IZettle\Api\IZettleRequestUri;

class DeleteProductFixture
{
    /**
     * @var string|null
     */
    public static $lastDeletedUuid;

    public static function delete(string $resourceUri): ?array
    {
        self::$lastDeletedUuid = \str_replace(IZettleRequestUri::PRODUCT_RESOURCE, '', $resourceUri);

        return null;
    }
}
