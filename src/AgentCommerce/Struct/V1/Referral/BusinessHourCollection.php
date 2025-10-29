<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Referral;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiCollection;

/**
 * @experimental
 *
 * @extends PayPalApiCollection<BusinessHour>
 */
#[Package('checkout')]
class BusinessHourCollection extends PayPalApiCollection
{
    public static function getExpectedClass(): string
    {
        return BusinessHour::class;
    }
}
