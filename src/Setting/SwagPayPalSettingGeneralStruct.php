<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PaymentIntent;

class SwagPayPalSettingGeneralStruct extends Struct
{
    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var bool
     */
    protected $sandbox = false;

    /**
     * @var string
     */
    protected $intent = PaymentIntent::SALE;

    /**
     * @var bool
     */
    protected $submitCart = false;

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
    protected $landingPage = ApplicationContext::LANDINGPAGE_TYPE_LOGIN;

    /**
     * @var bool
     */
    protected $sendOrderNumber = false;

    /**
     * @var string|null
     */
    protected $orderNumberPrefix;

    /**
     * @var bool
     */
    protected $ecsCartEnabled;

    /**
     * @var bool
     */
    protected $ecsOffCanvasEnabled;

    /**
     * @var bool
     */
    protected $ecsLoginEnabled;

    /**
     * @var string
     */
    protected $ecsButtonColor = 'gold';

    /**
     * @var string
     */
    protected $ecsButtonShape = 'rect';

    /**
     * @var bool
     */
    protected $ecsSubmitCart;

    /**
     * @var string|null
     */
    protected $ecsButtonLanguageIso;

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
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

    public function getEcsCartEnabled(): bool
    {
        return $this->ecsCartEnabled;
    }

    public function setEcsCartEnabled(bool $ecsCartEnabled): void
    {
        $this->ecsCartEnabled = $ecsCartEnabled;
    }

    public function getEcsOffCanvasEnabled(): bool
    {
        return $this->ecsOffCanvasEnabled;
    }

    public function setEcsOffCanvasEnabled(bool $ecsOffCanvasEnabled): void
    {
        $this->ecsOffCanvasEnabled = $ecsOffCanvasEnabled;
    }

    public function getEcsLoginEnabled(): bool
    {
        return $this->ecsLoginEnabled;
    }

    public function setEcsLoginEnabled(bool $ecsLoginEnabled): void
    {
        $this->ecsLoginEnabled = $ecsLoginEnabled;
    }

    public function getEcsButtonColor(): string
    {
        return $this->ecsButtonColor;
    }

    public function setEcsButtonColor(string $ecsButtonColor): void
    {
        $this->ecsButtonColor = $ecsButtonColor;
    }

    public function getEcsButtonShape(): string
    {
        return $this->ecsButtonShape;
    }

    public function setEcsButtonShape(string $ecsButtonShape): void
    {
        $this->ecsButtonShape = $ecsButtonShape;
    }

    public function getEcsSubmitCart(): bool
    {
        return $this->ecsSubmitCart;
    }

    public function setEcsSubmitCart(bool $ecsSubmitCart): void
    {
        $this->ecsSubmitCart = $ecsSubmitCart;
    }

    public function getEcsButtonLanguageIso(): ?string
    {
        return $this->ecsButtonLanguageIso;
    }

    public function setEcsButtonLanguageIso(string $ecsButtonLanguageIso): void
    {
        $this->ecsButtonLanguageIso = $ecsButtonLanguageIso;
    }
}
