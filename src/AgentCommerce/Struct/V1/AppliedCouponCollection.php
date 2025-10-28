<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Collection;

/**
 * @experimental
 *
 * @extends Collection<AppliedCoupon>
 */
#[Package('checkout')]
class AppliedCouponCollection extends Collection
{
    protected function getExpectedClass(): string
    {
        return AppliedCoupon::class;
    }
}
