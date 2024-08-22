<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\DuplicateTransaction\OriginalTransaction;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions_duplicate_transaction")
 */
#[Package('checkout')]
class DuplicateTransaction extends PayPalApiStruct
{
    /**
     * @OA\Property(type="boolean")
     */
    protected bool $receivedDuplicate;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_common_transaction")
     */
    protected OriginalTransaction $originalTransaction;

    public function isReceivedDuplicate(): bool
    {
        return $this->receivedDuplicate;
    }

    public function setReceivedDuplicate(bool $receivedDuplicate): void
    {
        $this->receivedDuplicate = $receivedDuplicate;
    }

    public function getOriginalTransaction(): OriginalTransaction
    {
        return $this->originalTransaction;
    }

    public function setOriginalTransaction(OriginalTransaction $originalTransaction): void
    {
        $this->originalTransaction = $originalTransaction;
    }
}
