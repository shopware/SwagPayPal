<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Subscription\BillingInfo;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Subscription\BillingInfo\LastPayment\Amount;

class LastPayment extends PayPalStruct
{
    /** @var Amount */
    protected $amount;

    /** @var string */
    protected $time;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;

        return $this;
    }
}
