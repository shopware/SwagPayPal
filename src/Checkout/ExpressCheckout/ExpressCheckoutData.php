<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Framework\Struct\Struct;

class ExpressCheckoutData extends Struct
{
    /**
     * @var string
     */
    private $payerId;

    /**
     * @var string
     */
    private $paymentId;

    public function __construct(string $paymentId, string $payerId)
    {
        $this->paymentId = $paymentId;
        $this->payerId = $payerId;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }
}
