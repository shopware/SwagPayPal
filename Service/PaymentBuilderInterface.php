<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Service;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Struct\Payment;

interface PaymentBuilderInterface
{
    /**
     * The function returns an array with all parameters that are expected by the PayPal API.
     */
    public function getPayment(PaymentTransactionStruct $paymentTransaction, Context $context): Payment;
}
