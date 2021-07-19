<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Sale\TransactionFee;

class Sale extends RelatedResource
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var TransactionFee
     */
    protected $transactionFee;

    public function getTransactionFee(): TransactionFee
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }
}
