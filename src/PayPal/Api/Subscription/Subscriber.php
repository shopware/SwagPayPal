<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Subscription;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Subscription\Subscriber\Name;

class Subscriber extends PayPalStruct
{
    /** @var Name */
    protected $name;

    /** @var string */
    protected $emailAddress;

    /** @var string */
    protected $payerId;

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }

    public function setPayerId(string $payerId): self
    {
        $this->payerId = $payerId;

        return $this;
    }
}
