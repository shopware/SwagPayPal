<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount;

class CaptureRefund
{
    /**
     * @var Amount
     */
    private $amount;

    /**
     * @var string
     */
    private $description;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function toArray(): array
    {
        //If the amount object is null, we do not need to add it to the array.
        //Note: A sale/capture will be refunded completely in that case
        return $this->getAmount() === null
            ? ['description' => $this->getDescription()]
            : ['description' => $this->getDescription(), 'amount' => $this->getAmount()->toArray()];
    }
}
