<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_referral_partner_config_override')]
#[Package('checkout')]
class PartnerConfigOverride extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $returnUrl;

    #[OA\Property(type: 'string')]
    protected string $partnerLogoUrl = 'https://assets.shopware.com/media/logos/shopware_logo_blue.svg';

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    public function getPartnerLogoUrl(): string
    {
        return $this->partnerLogoUrl;
    }

    public function setPartnerLogoUrl(string $partnerLogoUrl): void
    {
        $this->partnerLogoUrl = $partnerLogoUrl;
    }
}
