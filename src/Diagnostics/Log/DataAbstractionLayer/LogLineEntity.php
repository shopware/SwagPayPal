<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Log\DataAbstractionLayer;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class LogLineEntity extends Entity
{
    use EntityIdTrait;

    protected int $level;

    protected ?string $code = null;

    protected ?string $salesChannelId = null;

    protected ?string $uid = null;

    protected string $message;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $context = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $extra = null;

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): void
    {
        $this->uid = $uid;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExtra(): ?array
    {
        return $this->extra;
    }

    /**
     * @param array<string, mixed>|null $extra
     */
    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }
}
