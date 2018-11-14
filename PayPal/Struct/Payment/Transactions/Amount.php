<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Transactions;

use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount\Details;

class Amount
{
    /**
     * @var string
     */
    private $currency;

    /**
     * @var float
     */
    private $total;

    /**
     * @var Details
     */
    private $details;

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function getDetails(): Details
    {
        return $this->details;
    }

    public function setDetails(Details $details): void
    {
        $this->details = $details;
    }

    public static function fromArray(array $data): Amount
    {
        $result = new self();

        $result->setCurrency($data['currency']);
        $result->setTotal((float) $data['total']);

        if ($data['details'] !== null) {
            $result->setDetails(Details::fromArray($data['details']));
        }

        return $result;
    }

    public function toArray(): array
    {
        $result = [
            'currency' => $this->getCurrency(),
            'total' => $this->getTotal(),
        ];

        if ($this->getDetails() !== null) {
            $result['details'] = $this->getDetails()->toArray();
        }

        return $result;
    }
}
