<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source_sofort")
 *
 * @deprecated tag:v10.0.0 - will be removed, payment method has been disabled
 */
class Sofort extends AbstractAPMPaymentSource
{
}
