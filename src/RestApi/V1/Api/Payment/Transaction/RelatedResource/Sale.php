<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Sale\TransactionFee;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_transaction_sale")
 */
class Sale extends RelatedResource
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected TransactionFee $transactionFee;

    public function getTransactionFee(): TransactionFee
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }
}
