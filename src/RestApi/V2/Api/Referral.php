<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\BusinessEntity;
use Swag\PayPal\RestApi\V2\Api\Referral\LegalConsent;
use Swag\PayPal\RestApi\V2\Api\Referral\Link;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation;
use Swag\PayPal\RestApi\V2\Api\Referral\PartnerConfigOverride;

class Referral extends PayPalApiStruct
{
    public const PRODUCT_TYPE_EXPRESS_CHECKOUT = 'EXPRESS_CHECKOUT';

    /**
     * @var string
     */
    protected $trackingId;

    /**
     * @var string
     */
    protected $preferredLanguageCode;

    /**
     * @var Operation[]
     */
    protected $operations;

    /**
     * @var PartnerConfigOverride
     */
    protected $partnerConfigOverride;

    /**
     * @var LegalConsent[]
     */
    protected $legalConsents;

    /**
     * @var string[]
     */
    protected $products = [self::PRODUCT_TYPE_EXPRESS_CHECKOUT];

    /**
     * @var Link[]
     */
    protected $links;

    protected BusinessEntity $businessEntity;

    public function getTrackingId(): string
    {
        return $this->trackingId;
    }

    public function setTrackingId(string $trackingId): void
    {
        $this->trackingId = $trackingId;
    }

    public function getPreferredLanguageCode(): string
    {
        return $this->preferredLanguageCode;
    }

    public function setPreferredLanguageCode(string $preferredLanguageCode): void
    {
        $this->preferredLanguageCode = $preferredLanguageCode;
    }

    /**
     * @return Operation[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param Operation[] $operations
     */
    public function setOperations(array $operations): void
    {
        $this->operations = $operations;
    }

    public function getPartnerConfigOverride(): PartnerConfigOverride
    {
        return $this->partnerConfigOverride;
    }

    public function setPartnerConfigOverride(PartnerConfigOverride $partnerConfigOverride): void
    {
        $this->partnerConfigOverride = $partnerConfigOverride;
    }

    /**
     * @return LegalConsent[]
     */
    public function getLegalConsents(): array
    {
        return $this->legalConsents;
    }

    /**
     * @param LegalConsent[] $legalConsents
     */
    public function setLegalConsents(array $legalConsents): void
    {
        $this->legalConsents = $legalConsents;
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

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks(array $links): void
    {
        $this->links = $links;
    }

    public function getBusinessEntity(): BusinessEntity
    {
        return $this->businessEntity;
    }

    public function setBusinessEntity(BusinessEntity $businessEntity): void
    {
        $this->businessEntity = $businessEntity;
    }
}
