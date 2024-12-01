<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Struct;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[OA\Schema(schema: 'swag_paypal_setting_settings_information')]
#[Package('checkout')]
class SettingsInformationStruct extends Struct
{
    #[OA\Property(type: 'boolean')]
    protected bool $sandboxCredentialsChanged = false;

    #[OA\Property(type: 'boolean', nullable: true)]
    protected ?bool $sandboxCredentialsValid = null;

    #[OA\Property(type: 'boolean')]
    protected bool $liveCredentialsChanged = false;

    #[OA\Property(type: 'boolean', nullable: true)]
    protected ?bool $liveCredentialsValid = null;

    /**
     * @var array<string>
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $webhookErrors = [];

    public function getSandboxCredentialsChanged(): bool
    {
        return $this->sandboxCredentialsChanged;
    }

    public function setSandboxCredentialsChanged(bool $sandboxCredentialsChanged): void
    {
        $this->sandboxCredentialsChanged = $sandboxCredentialsChanged;
    }

    public function getSandboxCredentialsValid(): ?bool
    {
        return $this->sandboxCredentialsValid;
    }

    public function setSandboxCredentialsValid(?bool $sandboxCredentialsValid): void
    {
        $this->sandboxCredentialsValid = $sandboxCredentialsValid;
    }

    public function getLiveCredentialsChanged(): bool
    {
        return $this->liveCredentialsChanged;
    }

    public function setLiveCredentialsChanged(bool $liveCredentialsChanged): void
    {
        $this->liveCredentialsChanged = $liveCredentialsChanged;
    }

    public function getLiveCredentialsValid(): ?bool
    {
        return $this->liveCredentialsValid;
    }

    public function setLiveCredentialsValid(?bool $liveCredentialsValid): void
    {
        $this->liveCredentialsValid = $liveCredentialsValid;
    }

    /**
     * @return array<string>
     */
    public function getWebhookErrors(): array
    {
        return $this->webhookErrors;
    }

    /**
     * @param array<string> $webhookErrors
     */
    public function setWebhookErrors(array $webhookErrors): void
    {
        $this->webhookErrors = $webhookErrors;
    }
}
