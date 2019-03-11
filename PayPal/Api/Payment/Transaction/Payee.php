<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment\Transaction;

use SwagPayPal\PayPal\Api\Common\PayPalStruct;

class Payee extends PayPalStruct
{
    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $email;

    protected function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    protected function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
