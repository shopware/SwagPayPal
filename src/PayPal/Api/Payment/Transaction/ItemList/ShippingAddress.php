<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;

use Swag\PayPal\PayPal\Api\Common\Address;

class ShippingAddress extends Address
{
    /**
     * @var string
     */
    private $recipientName;

    public function setRecipientName(string $recipientName): void
    {
        $this->recipientName = $recipientName;
    }
}
