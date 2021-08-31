<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\DuplicateTransaction\OriginalTransaction;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions_duplicate_transaction")
 */
class DuplicateTransaction extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     * @OA\Property(type="boolean")
     */
    protected $receivedDuplicate;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var OriginalTransaction
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_common_transaction")
     */
    protected $originalTransaction;

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
