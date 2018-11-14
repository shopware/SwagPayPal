<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Transactions;

class ShipmentDetails
{
    /**
     * @var string
     */
    private $estimatedDeliveryDate;

    public function getEstimatedDeliveryDate(): string
    {
        return $this->estimatedDeliveryDate;
    }

    public function setEstimatedDeliveryDate(string $estimatedDeliveryDate): void
    {
        $this->estimatedDeliveryDate = $estimatedDeliveryDate;
    }

    public static function fromArray(array $data = []): ShipmentDetails
    {
        $result = new self();

        $result->setEstimatedDeliveryDate($data['estimated_delivery_date']);

        return $result;
    }

    public function toArray(): array
    {
        return [
            'estimated_delivery_date' => $this->getEstimatedDeliveryDate(),
        ];
    }
}
