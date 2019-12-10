<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment\Payer;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class ExecutePayerInfo extends PayPalStruct
{
    /**
     * @var string
     */
    protected $payerId;

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }
}
