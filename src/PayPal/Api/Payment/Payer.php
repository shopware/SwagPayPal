<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\Payer\PayerInfo;

class Payer extends PayPalStruct
{
    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var PayerInfo
     */
    protected $payerInfo;

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    protected function setStatus(string $status): void
    {
        $this->status = $status;
    }

    protected function setPayerInfo(PayerInfo $payerInfo): void
    {
        $this->payerInfo = $payerInfo;
    }
}
