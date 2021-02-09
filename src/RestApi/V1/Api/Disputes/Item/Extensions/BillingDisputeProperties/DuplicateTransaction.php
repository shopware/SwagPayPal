<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\DuplicateTransaction\OriginalTransaction;

class DuplicateTransaction extends PayPalApiStruct
{
    /**
     * @var bool
     */
    protected $receivedDuplicate;

    /**
     * @var OriginalTransaction
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
