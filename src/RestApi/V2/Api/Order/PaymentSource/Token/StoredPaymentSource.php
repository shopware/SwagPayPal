<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Token;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_token_stored_payment_source')]
#[Package('checkout')]
class StoredPaymentSource extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $paymentInitiator;

    #[OA\Property(type: 'string')]
    protected string $paymentType;

    #[OA\Property(type: 'string')]
    protected string $usage;

    public function getPaymentInitiator(): string
    {
        return $this->paymentInitiator;
    }

    public function setPaymentInitiator(string $paymentInitiator): void
    {
        $this->paymentInitiator = $paymentInitiator;
    }

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): void
    {
        $this->paymentType = $paymentType;
    }

    public function getUsage(): string
    {
        return $this->usage;
    }

    public function setUsage(string $usage): void
    {
        $this->usage = $usage;
    }
}
