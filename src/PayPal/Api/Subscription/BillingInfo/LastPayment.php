<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Subscription\BillingInfo;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Subscription\BillingInfo\LastPayment\Amount;

/**
 * @codeCoverageIgnore
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
class LastPayment extends PayPalStruct
{
    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var string
     */
    protected $time;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function setTime(string $time): void
    {
        $this->time = $time;
    }
}
