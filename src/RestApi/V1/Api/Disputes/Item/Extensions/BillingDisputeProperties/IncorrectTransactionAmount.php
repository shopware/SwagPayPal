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
use Swag\PayPal\RestApi\V1\Api\Common\Money;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_extensions_billing_dispute_properties_incorrect_transaction_amount')]
#[Package('checkout')]
class IncorrectTransactionAmount extends PayPalApiStruct
{
    #[OA\Property(ref: Money::class)]
    protected Money $correctTransactionAmount;

    #[OA\Property(type: 'string')]
    protected string $correctTransactionTime;

    public function getCorrectTransactionAmount(): Money
    {
        return $this->correctTransactionAmount;
    }

    public function setCorrectTransactionAmount(Money $correctTransactionAmount): void
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
