<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Credit;

class Price
{
    /**
     * @var float
     */
    private $value;

    /**
     * @var string
     */
    private $currency;

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public static function fromArray(array $data): Price
    {
        $result = new self();
        $result->setValue((float) $data['value']);
        $result->setCurrency($data['currency']);

        return $result;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'value' => $this->getValue(),
            'currency' => $this->getCurrency(),
        ];
    }
}
