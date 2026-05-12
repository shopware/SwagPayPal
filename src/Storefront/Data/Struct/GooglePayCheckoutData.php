<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class GooglePayCheckoutData extends AbstractCheckoutData
{
    protected string $totalPrice;

    protected bool $sandbox;

    /**
     * @var mixed[]
     */
    protected array $displayItems = [];

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function setSandbox(bool $sandbox): void
    {
        $this->sandbox = $sandbox;
    }

    /**
     * @return mixed[]
     */
    public function getDisplayItems(): array
    {
        return $this->displayItems;
    }

    /**
     * @param mixed[] $displayItems
     */
    public function setDisplayItems(array $displayItems): void
    {
        $this->displayItems = $displayItems;
    }
}
