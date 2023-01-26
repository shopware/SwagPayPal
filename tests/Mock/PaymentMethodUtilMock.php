<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Shopware\Core\Framework\Context;
use Swag\PayPal\Util\PaymentMethodUtil;

class PaymentMethodUtilMock extends PaymentMethodUtil
{
    public const PAYMENT_METHOD_ID = 'cfbd5018d38d41a8adcae0d94fc8bddc';

    public function __construct()
    {
    }

    public function getPayPalPaymentMethodId(Context $context): string
    {
        return self::PAYMENT_METHOD_ID;
    }
}
