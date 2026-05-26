<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiCollection;

/**
 * @experimental
 *
 * @extends PayPalApiCollection<Link>
 */
#[Package('checkout')]
class LinkCollection extends PayPalApiCollection
{
    public static function getExpectedClass(): string
    {
        return Link::class;
    }
}
