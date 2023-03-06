<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\OauthIntegration;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product;

/**
 * @OA\Schema(schema="swag_paypal_v1_merchant_integrations")
 */
class MerchantIntegrations extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $merchantId;

    /**
     * @OA\Property(type="string")
     */
    protected string $trackingId;

    /**
     * @var Product[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_merchant_integrations_product"})
     */
    protected array $products = [];

    /**
     * @var Capability[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_merchant_integrations_capability"})
     */
    protected array $capabilities = [];

    /**
     * @var OauthIntegration[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_merchant_integrations_oauth_integration"})
     */
    protected array $oauthIntegrations = [];

    /**
     * @var string[]
     *
     * @OA\Property(type="array", items={"type": "string"})
     */
    protected array $grantedPermissions = [];

    /**
     * @OA\Property(type="boolean")
     */
    protected bool $paymentsReceivable;

    /**
     * @OA\Property(type="string")
     */
    protected string $legalName;

    /**
     * @OA\Property(type="string")
     */
    protected string $primaryEmail;

    /**
     * @OA\Property(type="boolean")
     */
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

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    /**
     * @return Capability[]
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    public function getPaymentsReceivable(): bool
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

    public function getPrimaryEmailConfirmed(): bool
    {
        return $this->primaryEmailConfirmed;
    }

    public function setPrimaryEmailConfirmed(bool $primaryEmailConfirmed): void
    {
        $this->primaryEmailConfirmed = $primaryEmailConfirmed;
    }

    public function getGrantedPermissions(): array
    {
        return $this->grantedPermissions;
    }

    public function setGrantedPermissions(array $grantedPermissions): void
    {
        $this->grantedPermissions = $grantedPermissions;
    }

    /**
     * @return OauthIntegration[]
     */
    public function getOauthIntegrations(): array
    {
        return $this->oauthIntegrations;
    }

    public function setOauthIntegrations(array $oauthIntegrations): void
    {
        $this->oauthIntegrations = $oauthIntegrations;
    }

    public function getSpecificCapability(string $name): ?Capability
    {
        foreach ($this->capabilities as $capability) {
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
