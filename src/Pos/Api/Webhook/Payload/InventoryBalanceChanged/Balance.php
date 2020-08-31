<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Payload\InventoryBalanceChanged;

use Swag\PayPal\Pos\Api\Common\PosStruct;

abstract class Balance extends PosStruct
{
    /**
     * @var string
     */
    protected $organizationUuid;

    /**
     * @var string
     */
    protected $locationUuid;

    /**
     * @var string
     */
    protected $productUuid;

    /**
     * @var string
     */
    protected $variantUuid;

    /**
     * @var int
     */
    protected $balance;

    public function getLocationUuid(): string
    {
        return $this->locationUuid;
    }

    public function getProductUuid(): string
    {
        return $this->productUuid;
    }

    public function getVariantUuid(): string
    {
        return $this->variantUuid;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    protected function setOrganizationUuid(string $organizationUuid): void
    {
        $this->organizationUuid = $organizationUuid;
    }

    protected function setLocationUuid(string $locationUuid): void
    {
        $this->locationUuid = $locationUuid;
    }

    protected function setProductUuid(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    protected function setVariantUuid(string $variantUuid): void
    {
        $this->variantUuid = $variantUuid;
    }

    protected function setBalance(string $balance): void
    {
        $this->balance = (int) $balance;
    }
}
