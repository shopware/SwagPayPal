<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V2\Api\Referral\BusinessEntity;
use Swag\PayPal\RestApi\V2\Api\Referral\LegalConsent;
use Swag\PayPal\RestApi\V2\Api\Referral\LegalConsentCollection;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation;
use Swag\PayPal\RestApi\V2\Api\Referral\OperationCollection;
use Swag\PayPal\RestApi\V2\Api\Referral\PartnerConfigOverride;

#[OA\Schema(schema: 'swag_paypal_v2_referral')]
#[Package('checkout')]
class Referral extends PayPalApiStruct
{
    public const PRODUCT_TYPE_PPCP = 'PPCP';
    public const PRODUCT_TYPE_PAYMENT_METHODS = 'PAYMENT_METHODS';
    public const PRODUCT_TYPE_ADVANCED_VAULTING = 'ADVANCED_VAULTING';

    public const CAPABILITY_PAYPAL_WALLET_VAULTING_ADVANCED = 'PAYPAL_WALLET_VAULTING_ADVANCED';
    public const CAPABILITY_PAY_UPON_INVOICE = 'PAY_UPON_INVOICE';
    public const CAPABILITY_APPLE_PAY = 'APPLE_PAY';
    public const CAPABILITY_GOOGLE_PAY = 'GOOGLE_PAY';

    #[OA\Property(ref: BusinessEntity::class)]
    protected BusinessEntity $businessEntity;

    #[OA\Property(type: 'string')]
    protected string $preferredLanguageCode;

    #[OA\Property(type: 'string')]
    protected string $trackingId;

    #[OA\Property(ref: PartnerConfigOverride::class)]
    protected PartnerConfigOverride $partnerConfigOverride;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Operation::class))]
    protected OperationCollection $operations;

    /**
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $products = [
        self::PRODUCT_TYPE_PPCP,
        self::PRODUCT_TYPE_PAYMENT_METHODS,
        self::PRODUCT_TYPE_ADVANCED_VAULTING,
    ];

    /**
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $capabilities = [
        self::CAPABILITY_PAY_UPON_INVOICE,
        self::CAPABILITY_PAYPAL_WALLET_VAULTING_ADVANCED,
        self::CAPABILITY_APPLE_PAY,
        self::CAPABILITY_GOOGLE_PAY,
    ];

    #[OA\Property(type: 'array', items: new OA\Items(ref: LegalConsent::class))]
    protected LegalConsentCollection $legalConsents;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected LinkCollection $links;

    public function getBusinessEntity(): BusinessEntity
    {
        return $this->businessEntity;
    }

    public function setBusinessEntity(BusinessEntity $businessEntity): void
    {
        $this->businessEntity = $businessEntity;
    }

    public function getPreferredLanguageCode(): string
    {
        return $this->preferredLanguageCode;
    }

    public function setPreferredLanguageCode(string $preferredLanguageCode): void
    {
        $this->preferredLanguageCode = $preferredLanguageCode;
    }

    public function getTrackingId(): string
    {
        return $this->trackingId;
    }

    public function setTrackingId(string $trackingId): void
    {
        $this->trackingId = $trackingId;
    }

    public function getPartnerConfigOverride(): PartnerConfigOverride
    {
        return $this->partnerConfigOverride;
    }

    public function setPartnerConfigOverride(PartnerConfigOverride $partnerConfigOverride): void
    {
        $this->partnerConfigOverride = $partnerConfigOverride;
    }

    public function getOperations(): OperationCollection
    {
        return $this->operations;
    }

    public function setOperations(OperationCollection $operations): void
    {
        $this->operations = $operations;
    }

    /**
     * @return string[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param string[] $products
     */
    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    public function addProduct(string $product): void
    {
        $this->products[] = $product;
    }

    /**
     * @return string[]
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @param string[] $capabilities
     */
    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    public function getLegalConsents(): LegalConsentCollection
    {
        return $this->legalConsents;
    }

    public function setLegalConsents(LegalConsentCollection $legalConsents): void
    {
        $this->legalConsents = $legalConsents;
    }

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }
}
