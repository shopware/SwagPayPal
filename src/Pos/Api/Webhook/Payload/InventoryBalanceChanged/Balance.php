<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Payload\InventoryBalanceChanged;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
abstract class Balance extends PosStruct
{
    protected string $organizationUuid;

    protected string $locationUuid;

    protected string $productUuid;

    protected string $variantUuid;

    protected int $balance;

    public function getOrganizationUuid(): string
    {
        return $this->organizationUuid;
    }

    public function setOrganizationUuid(string $organizationUuid): void
    {
        $this->organizationUuid = $organizationUuid;
    }

    public function getLocationUuid(): string
    {
        return $this->locationUuid;
    }

    public function setLocationUuid(string $locationUuid): void
    {
        $this->locationUuid = $locationUuid;
    }

    public function getProductUuid(): string
    {
        return $this->productUuid;
    }

    public function setProductUuid(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    public function getVariantUuid(): string
    {
        return $this->variantUuid;
    }

    public function setVariantUuid(string $variantUuid): void
    {
        $this->variantUuid = $variantUuid;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function setBalance(string $balance): void
    {
        $this->balance = (int) $balance;
    }
}
