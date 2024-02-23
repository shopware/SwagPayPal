<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Token\StoredPaymentSource;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_token')]
#[Package('checkout')]
class Token extends AbstractPaymentSource
{
    public const TYPE_BILLING_AGREEMENT = 'BILLING_AGREEMENT';

    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(type: 'string')]
    protected string $type = self::TYPE_BILLING_AGREEMENT;

    #[OA\Property(ref: StoredPaymentSource::class)]
    protected ?StoredPaymentSource $storedPaymentSource = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getStoredPaymentSource(): ?StoredPaymentSource
    {
        return $this->storedPaymentSource;
    }

    public function setStoredPaymentSource(?StoredPaymentSource $storedPaymentSource): void
    {
        $this->storedPaymentSource = $storedPaymentSource;
    }
}
