<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Common;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiCollection;

/**
 * @extends PayPalApiCollection<Link>
 */
#[Package('checkout')]
class LinkCollection extends PayPalApiCollection
{
    public static function getExpectedClass(): string
    {
        return Link::class;
    }

    public function getRelation(string $rel): ?Link
    {
        foreach ($this->elements as $link) {
            if ($link->getRel() === $rel) {
                return $link;
            }
        }

        return null;
    }
}
