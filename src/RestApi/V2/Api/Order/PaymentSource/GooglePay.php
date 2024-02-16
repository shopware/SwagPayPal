<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_google_pay')]
#[Package('checkout')]
class GooglePay extends AbstractPaymentSource
{
    #[OA\Property(ref: Card::class, nullable: true)]
    protected ?Card $card = null;

    #[OA\Property(ref: Attributes::class, nullable: true)]
    protected ?Attributes $attributes = null;

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): void
    {
        $this->card = $card;
    }

    public function getAttributes(): ?Attributes
    {
        return $this->attributes;
    }

    public function setAttributes(?Attributes $attributes): void
    {
        $this->attributes = $attributes;
    }
}
