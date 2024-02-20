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

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_common_attributes_verification')]
#[Package('checkout')]
class Verification extends PayPalApiStruct
{
    public const METHOD_SCA_WHEN_REQUIRED = 'SCA_WHEN_REQUIRED';
    public const METHOD_SCA_ALWAYS = 'SCA_ALWAYS';

    #[OA\Property(type: 'string')]
    protected string $method = self::METHOD_SCA_ALWAYS;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }
}
