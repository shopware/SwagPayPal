<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\V1\Api\Disputes\Common\Transaction;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_disputed_transaction")
 */
class DisputedTransaction extends Transaction
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     * @OA\Property(type="boolean")
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
