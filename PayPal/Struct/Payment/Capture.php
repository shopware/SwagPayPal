<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount;

class Capture
{
    /**
     * @var Amount
     */
    private $amount;

    /**
     * @var bool
     */
    private $isFinalCapture;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getIsFinalCapture(): bool
    {
        return $this->isFinalCapture;
    }

    public function setIsFinalCapture(bool $isFinalCapture): void
    {
        $this->isFinalCapture = $isFinalCapture;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->getAmount()->toArray(),
            'is_final_capture' => $this->getIsFinalCapture(),
        ];
    }
}
