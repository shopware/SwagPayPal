<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Boletobancario;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_boletobancario_tax_info')]
#[Package('checkout')]
class TaxInfo extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $taxId;

    #[OA\Property(type: 'string')]
    protected string $taxIdType;

    public function getTaxId(): string
    {
        return $this->taxId;
    }

    public function setTaxId(string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function getTaxIdType(): string
    {
        return $this->taxIdType;
    }

    public function setTaxIdType(string $taxIdType): void
    {
        $this->taxIdType = $taxIdType;
    }
}
