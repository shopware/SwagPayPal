<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Common\Value;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_transaction_sale")
 */
#[Package('checkout')]
class Sale extends RelatedResource
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected Value $transactionFee;

    public function getTransactionFee(): Value
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(Value $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }
}
