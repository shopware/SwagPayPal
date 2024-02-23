<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Disputes\Common\Transaction;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_disputed_transaction')]
#[Package('checkout')]
class DisputedTransaction extends Transaction
{
    #[OA\Property(type: 'boolean')]
    protected bool $sellerProtectionEligible;

    public function isSellerProtectionEligible(): bool
    {
        return $this->sellerProtectionEligible;
    }

    public function setSellerProtectionEligible(bool $sellerProtectionEligible): void
    {
        $this->sellerProtectionEligible = $sellerProtectionEligible;
    }
}
