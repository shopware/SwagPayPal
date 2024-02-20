<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_common_sub_reason')]
#[Package('checkout')]
class SubReason extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $subReason;

    public function getSubReason(): string
    {
        return $this->subReason;
    }

    public function setSubReason(string $subReason): void
    {
        $this->subReason = $subReason;
    }
}
