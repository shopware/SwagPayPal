<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_trustly')]
#[Package('checkout')]
class Trustly extends AbstractAPMPaymentSource
{
}
