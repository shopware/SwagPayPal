<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Setting;

use DateTime;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class SwagPayPalSettingGeneralStruct extends Entity
{
    /**
     * @var string|null
     */
    protected $clientId;

    /**
     * @var string|null
     */
    protected $clientSecret;

    /**
     * @var bool
     */
    protected $sandbox;

    /**
     * @var string|null
     */
    protected $webhookId;

    /**
     * @var string|null
     */
    protected $webhookExecuteToken;

    /**
     * @var string|null
     */
    protected $brandName;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getSandbox(): bool
    {
        return $this->sandbox;
    }

    public function setSandbox(bool $sandbox): void
    {
        $this->sandbox = $sandbox;
    }

    public function getWebhookId(): ?string
    {
        return $this->webhookId;
    }

    public function setWebhookId(string $webhookId): void
    {
        $this->webhookId = $webhookId;
    }

    public function getWebhookExecuteToken(): ?string
    {
        return $this->webhookExecuteToken;
    }

    public function setWebhookExecuteToken(string $webhookExecuteToken): void
    {
        $this->webhookExecuteToken = $webhookExecuteToken;
    }

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
