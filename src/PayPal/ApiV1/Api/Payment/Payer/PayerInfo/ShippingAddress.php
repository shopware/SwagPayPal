<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV1\Api\Payment\Payer\PayerInfo;

use Swag\PayPal\PayPal\ApiV1\Api\Common\Address;

class ShippingAddress extends Address
{
    /**
     * @var string
     */
    protected $recipientName;

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): void
    {
        $this->recipientName = $recipientName;
    }
}
