<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\CapabilityCollection;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\OauthIntegration;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\OauthIntegrationCollection;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\ProductCollection;

#[OA\Schema(schema: 'swag_paypal_v1_merchant_integrations')]
#[Package('checkout')]
class MerchantIntegrations extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $merchantId;

    #[OA\Property(type: 'string')]
    protected string $trackingId;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Product::class))]
    protected ProductCollection $products;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Capability::class), nullable: true)]
    protected ?CapabilityCollection $capabilities = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: OauthIntegration::class))]
    protected OauthIntegrationCollection $oauthIntegrations;

    /**
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $grantedPermissions = [];

    #[OA\Property(type: 'boolean')]
    protected bool $paymentsReceivable;

    #[OA\Property(type: 'string')]
    protected string $legalName;

    #[OA\Property(type: 'string')]
    protected string $primaryEmail;

    #[OA\Property(type: 'boolean')]
    protected bool $primaryEmailConfirmed;

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function getTrackingId(): string
    {
        return $this->trackingId;
    }

    public function setTrackingId(string $trackingId): void
    {
        $this->trackingId = $trackingId;
    }

    public function getProducts(): ProductCollection
    {
        return $this->products;
    }

    public function setProducts(ProductCollection $products): void
    {
        $this->products = $products;
    }

    public function getCapabilities(): ?CapabilityCollection
    {
        return $this->capabilities;
    }

    public function setCapabilities(?CapabilityCollection $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    public function isPaymentsReceivable(): bool
    {
        return $this->paymentsReceivable;
    }

    public function setPaymentsReceivable(bool $paymentsReceivable): void
    {
        $this->paymentsReceivable = $paymentsReceivable;
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): void
    {
        $this->legalName = $legalName;
    }

    public function getPrimaryEmail(): string
    {
        return $this->primaryEmail;
    }

    public function setPrimaryEmail(string $primaryEmail): void
    {
        $this->primaryEmail = $primaryEmail;
    }

    public function isPrimaryEmailConfirmed(): bool
    {
        return $this->primaryEmailConfirmed;
    }

    public function setPrimaryEmailConfirmed(bool $primaryEmailConfirmed): void
    {
        $this->primaryEmailConfirmed = $primaryEmailConfirmed;
    }

    /**
     * @return string[]
     */
    public function getGrantedPermissions(): array
    {
        return $this->grantedPermissions;
    }

    /**
     * @param string[] $grantedPermissions
     */
    public function setGrantedPermissions(array $grantedPermissions): void
    {
        $this->grantedPermissions = $grantedPermissions;
    }

    public function getOauthIntegrations(): OauthIntegrationCollection
    {
        return $this->oauthIntegrations;
    }

    public function setOauthIntegrations(OauthIntegrationCollection $oauthIntegrations): void
    {
        $this->oauthIntegrations = $oauthIntegrations;
    }

    public function getSpecificCapability(string $name): ?Capability
    {
        foreach (($this->capabilities ?? []) as $capability) {
            if ($capability->getName() === $name) {
                return $capability;
            }
        }

        return null;
    }

    public function getSpecificProduct(string $name): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->getName() === $name) {
                return $product;
            }
        }

        return null;
    }
}
