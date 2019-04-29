<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SwagPayPalSettingGeneralEntity extends Entity
{
    use EntityIdTrait;

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
     * @var string
     */
    protected $intent;

    /**
     * @var bool
     */
    protected $submitCart;

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
     * @var string
     */
    protected $landingPage;

    /**
     * @var bool
     */
    protected $sendOrderNumber;

    /**
     * @var string|null
     */
    protected $orderNumberPrefix;

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

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function setSubmitCart(bool $submitCart): void
    {
        $this->submitCart = $submitCart;
    }

    public function getSubmitCart(): bool
    {
        return $this->submitCart;
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

    public function getLandingPage(): string
    {
        return $this->landingPage;
    }

    public function setLandingPage(string $landingPage): void
    {
        $this->landingPage = $landingPage;
    }

    public function getSendOrderNumber(): bool
    {
        return $this->sendOrderNumber;
    }

    public function setSendOrderNumber(bool $sendOrderNumber): void
    {
        $this->sendOrderNumber = $sendOrderNumber;
    }

    public function getOrderNumberPrefix(): ?string
    {
        return $this->orderNumberPrefix;
    }

    public function setOrderNumberPrefix(string $orderNumberPrefix): void
    {
        $this->orderNumberPrefix = $orderNumberPrefix;
    }
}
