<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Swag\PayPal\RestApi\V1\Api\Disputes\Common\Transaction;

class DisputedTransaction extends Transaction
{
    /**
     * @var bool
     */
    protected $sellerProtectionEligible;

    public function isSellerProtectionEligible(): bool
    {
        return $this->sellerProtectionEligible;
    }

    public function setSellerProtectionEligible(bool $sellerProtectionEligible): void
    {
        $this->sellerProtectionEligible = $sellerProtectionEligible;
    }
}
