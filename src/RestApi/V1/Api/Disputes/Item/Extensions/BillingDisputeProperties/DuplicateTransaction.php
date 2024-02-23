<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Common\Transaction;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_extensions_billing_dispute_properties_duplicate_transaction')]
#[Package('checkout')]
class DuplicateTransaction extends PayPalApiStruct
{
    #[OA\Property(type: 'boolean')]
    protected bool $receivedDuplicate;

    #[OA\Property(ref: Transaction::class)]
    protected Transaction $originalTransaction;

    public function isReceivedDuplicate(): bool
    {
        return $this->receivedDuplicate;
    }

    public function setReceivedDuplicate(bool $receivedDuplicate): void
    {
        $this->receivedDuplicate = $receivedDuplicate;
    }

    public function getOriginalTransaction(): Transaction
    {
        return $this->originalTransaction;
    }

    public function setOriginalTransaction(Transaction $originalTransaction): void
    {
        $this->originalTransaction = $originalTransaction;
    }
}
