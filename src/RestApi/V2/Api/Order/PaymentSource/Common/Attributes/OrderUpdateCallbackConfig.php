<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_common_attributes_order_update_callback_config')]
#[Package('checkout')]
class OrderUpdateCallbackConfig extends PayPalApiStruct
{
    public const CALLBACK_EVENT_SHIPPING_ADDRESS = 'SHIPPING_ADDRESS';
    public const CALLBACK_EVENT_SHIPPING_OPTIONS = 'SHIPPING_OPTIONS';

    #[OA\Property(type: 'string')]
    protected string $callbackUrl;

    /**
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: [self::CALLBACK_EVENT_SHIPPING_ADDRESS, self::CALLBACK_EVENT_SHIPPING_OPTIONS]))]
    protected array $callbackEvents;

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string[]
     */
    public function getCallbackEvents(): array
    {
        return $this->callbackEvents;
    }

    /**
     * @param string[] $callbackEvents
     */
    public function setCallbackEvents(array $callbackEvents): void
    {
        $this->callbackEvents = $callbackEvents;
    }
}
