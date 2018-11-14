<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Transactions\ItemList;

use SwagPayPal\PayPal\Struct\Common\Address;

class ShippingAddress extends Address
{
    /**
     * @var string
     */
    private $recipientName;

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): void
    {
        $this->recipientName = $recipientName;
    }

    public function toArray(): array
    {
        $result = parent::toArray();
        $result['recipient_name'] = $this->getRecipientName();

        return $result;
    }

    public static function fromArray(array $data = null): Address
    {
        $result = new self();

        if ($data === null) {
            return $result;
        }

        if (array_key_exists('city', $data)) {
            $result->setCity($data['city']);
        }
        if (array_key_exists('country_code', $data)) {
            $result->setCountryCode($data['country_code']);
        }
        if (array_key_exists('line1', $data)) {
            $result->setLine1($data['line1']);
        }
        if (array_key_exists('line2', $data)) {
            $result->setLine2($data['line2']);
        }
        if (array_key_exists('postal_code', $data)) {
            $result->setPostalCode($data['postal_code']);
        }
        if (array_key_exists('state', $data)) {
            $result->setState($data['state']);
        }
        if (array_key_exists('phone', $data)) {
            $result->setPhone($data['phone']);
        }
        if (array_key_exists('recipient_name', $data)) {
            $result->setRecipientName($data['recipient_name']);
        }

        return $result;
    }
}
