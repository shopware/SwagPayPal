<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;

#[OA\Schema(schema: 'swag_paypal_v1_payment_transaction_related_resource_order')]
#[Package('checkout')]
class Order extends RelatedResource
{
    #[OA\Property(type: 'string')]
    protected string $reasonCode;

    public function getReasonCode(): string
    {
        return $this->reasonCode;
    }

    public function setReasonCode(string $reasonCode): void
    {
        $this->reasonCode = $reasonCode;
    }
}
