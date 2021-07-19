<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Payer;

use Swag\PayPal\RestApi\PayPalApiStruct;

class ExecutePayerInfo extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $payerId;

    public function getPayerId(): string
    {
        return $this->payerId;
    }

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }
}
