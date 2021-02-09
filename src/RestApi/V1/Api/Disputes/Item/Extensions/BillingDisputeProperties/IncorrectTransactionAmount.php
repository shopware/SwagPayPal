<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\IncorrectTransactionAmount\CorrectTransactionAmount;

class IncorrectTransactionAmount extends PayPalApiStruct
{
    /**
     * @var CorrectTransactionAmount
     */
    protected $correctTransactionAmount;

    /**
     * @var string
     */
    protected $correctTransactionTime;

    public function getCorrectTransactionAmount(): CorrectTransactionAmount
    {
        return $this->correctTransactionAmount;
    }

    public function setCorrectTransactionAmount(CorrectTransactionAmount $correctTransactionAmount): void
    {
        $this->correctTransactionAmount = $correctTransactionAmount;
    }

    public function getCorrectTransactionTime(): string
    {
        return $this->correctTransactionTime;
    }

    public function setCorrectTransactionTime(string $correctTransactionTime): void
    {
        $this->correctTransactionTime = $correctTransactionTime;
    }
}
