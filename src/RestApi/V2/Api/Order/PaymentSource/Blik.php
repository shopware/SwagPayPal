<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source_blik")
 */
#[Package('checkout')]
class Blik extends AbstractAPMPaymentSource
{
    /**
     * @OA\Property(type="string")
     */
    protected string $email;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
