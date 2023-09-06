<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class ExpressCheckoutData extends Struct
{
    private string $paypalOrderId;

    public function __construct(string $paypalOrderId)
    {
        $this->paypalOrderId = $paypalOrderId;
    }

    public function getPaypalOrderId(): string
    {
        return $this->paypalOrderId;
    }
}
