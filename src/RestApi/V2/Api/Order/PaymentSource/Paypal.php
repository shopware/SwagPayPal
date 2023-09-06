<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal\Address;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal\Name;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source_paypal")
 */
#[Package('checkout')]
class Paypal extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $emailAddress;

    /**
     * @OA\Property(type="string")
     */
    protected string $accountId;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_name")
     */
    protected Name $name;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_address")
     */
    protected Address $address;
}
