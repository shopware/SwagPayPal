<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_card_stored_credential')]
#[Package('checkout')]
class StoredCredential extends PayPalApiStruct
{
    public const PAYMENT_INITIATOR_MERCHANT = 'MERCHANT';
    public const PAYMENT_INITIATOR_CUSTOMER = 'CUSTOMER';

    public const PAYMENT_TYPE_ONE_TIME = 'ONE_TIME';
    public const PAYMENT_TYPE_RECURRING = 'RECURRING';
    public const PAYMENT_TYPE_UNSCHEDULED = 'UNSCHEDULED';

    public const USAGE_FIRST = 'FIRST';
    public const USAGE_SUBSEQUENT = 'SUBSEQUENT';
    public const USAGE_DERIVED = 'DERIVED';

    #[OA\Property(type: 'string', enum: [self::PAYMENT_INITIATOR_MERCHANT, self::PAYMENT_INITIATOR_CUSTOMER])]
    protected string $paymentInitiator;

    #[OA\Property(type: 'string', enum: [self::PAYMENT_TYPE_RECURRING, self::PAYMENT_TYPE_ONE_TIME, self::PAYMENT_TYPE_UNSCHEDULED])]
    protected string $paymentType;

    #[OA\Property(type: 'string', enum: [self::USAGE_DERIVED, self::USAGE_FIRST, self::USAGE_SUBSEQUENT])]
    protected string $usage;

    #[OA\Property(type: 'string')]
    protected string $previousNetworkTransactionReference;

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

    public function getPreviousNetworkTransactionReference(): string
    {
        return $this->previousNetworkTransactionReference;
    }

    public function setPreviousNetworkTransactionReference(string $previousNetworkTransactionReference): void
    {
        $this->previousNetworkTransactionReference = $previousNetworkTransactionReference;
    }
}
