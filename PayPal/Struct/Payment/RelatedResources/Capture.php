<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

class Capture extends RelatedResource
{
    /**
     * @var TransactionFee
     */
    private $transactionFee;

    public function getTransactionFee(): TransactionFee
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
    }

    /**
     * @param array $data
     *
     * @return Capture
     */
    public static function fromArray(array $data): Capture
    {
        $result = new self();
        $result->prepare($result, $data, ResourceType::CAPTURE);

        if (\is_array($data['transaction_fee'])) {
            $result->setTransactionFee(TransactionFee::fromArray($data['transaction_fee']));
        }

        return $result;
    }
}
