<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payee\DisplayData;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_payee')]
#[Package('checkout')]
class Payee extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $emailAddress;

    #[OA\Property(type: 'string')]
    protected string $merchantId;

    #[OA\Property(ref: DisplayData::class)]
    protected DisplayData $displayData;

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function getDisplayData(): DisplayData
    {
        return $this->displayData;
    }

    public function setDisplayData(DisplayData $displayData): void
    {
        $this->displayData = $displayData;
    }
}
