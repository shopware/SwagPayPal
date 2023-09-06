<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\DataAbstractionLayer\Entity;

use Shopware\Core\Content\ProductStream\ProductStreamEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

#[Package('checkout')]
class PosSalesChannelEntity extends Entity
{
    use EntityIdTrait;

    public const REPLACE_PERMANENTLY = 2;
    public const REPLACE_ONE_TIME = 1;
    public const REPLACE_OFF = 0;

    private const REPLACE_VALID_VALUES = [
        self::REPLACE_PERMANENTLY,
        self::REPLACE_ONE_TIME,
        self::REPLACE_OFF,
    ];

    protected string $salesChannelId;

    protected ?SalesChannelEntity $salesChannel = null;

    protected ?string $productStreamId = null;

    protected ?ProductStreamEntity $productStream = null;

    protected string $apiKey;

    protected ?string $mediaDomain = null;

    protected ?string $webhookSigningKey = null;

    protected bool $syncPrices;

    protected int $replace;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getProductStreamId(): ?string
    {
        return $this->productStreamId;
    }

    public function setProductStreamId(?string $productStreamId): void
    {
        $this->productStreamId = $productStreamId;
    }

    public function getProductStream(): ?ProductStreamEntity
    {
        return $this->productStream;
    }

    public function setProductStream(?ProductStreamEntity $productStream): void
    {
        $this->productStream = $productStream;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getMediaDomain(): ?string
    {
        return $this->mediaDomain;
    }

    public function setMediaDomain(?string $mediaDomain): void
    {
        $this->mediaDomain = $mediaDomain;
    }

    public function getWebhookSigningKey(): ?string
    {
        return $this->webhookSigningKey;
    }

    public function setWebhookSigningKey(?string $webhookSigningKey): void
    {
        $this->webhookSigningKey = $webhookSigningKey;
    }

    public function isSyncPrices(): bool
    {
        return $this->syncPrices;
    }

    public function setSyncPrices(bool $syncPrices): void
    {
        $this->syncPrices = $syncPrices;
    }

    public function getReplace(): int
    {
        return $this->replace;
    }

    public function setReplace(int $replace): void
    {
        if (!\in_array($replace, self::REPLACE_VALID_VALUES, true)) {
            throw new ConstraintDefinitionException(\sprintf(
                'Replace must be one of these values: %s',
                \implode(', ', self::REPLACE_VALID_VALUES)
            ));
        }

        $this->replace = $replace;
    }
}
