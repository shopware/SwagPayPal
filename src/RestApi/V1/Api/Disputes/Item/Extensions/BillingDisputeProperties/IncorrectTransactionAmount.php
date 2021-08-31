<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\IncorrectTransactionAmount\CorrectTransactionAmount;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions_incorrect_transaction_amount")
 */
class IncorrectTransactionAmount extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var CorrectTransactionAmount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected $correctTransactionAmount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
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
