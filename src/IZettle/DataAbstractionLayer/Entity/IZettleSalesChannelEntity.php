<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class IZettleSalesChannelEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var string|null
     */
    protected $productStreamId;

    /**
     * @var string
     */
    protected $salesChannelDomainId;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var bool
     */
    protected $syncPrices;

    /**
     * @var bool
     */
    protected $replace;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getProductStreamId(): ?string
    {
        return $this->productStreamId;
    }

    public function setProductStreamId(?string $productStreamId): void
    {
        $this->productStreamId = $productStreamId;
    }

    public function getSalesChannelDomainId(): string
    {
        return $this->salesChannelDomainId;
    }

    public function setSalesChannelDomainId(string $salesChannelDomainId): void
    {
        $this->salesChannelDomainId = $salesChannelDomainId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function isSyncPrices(): bool
    {
        return $this->syncPrices;
    }

    public function setSyncPrices(bool $syncPrices): void
    {
        $this->syncPrices = $syncPrices;
    }

    public function isReplace(): bool
    {
        return $this->replace;
    }

    public function setReplace(bool $replace): void
    {
        $this->replace = $replace;
    }
}
