<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Referral;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_referral_suggested_correction')]
class SuggestedCorrection extends Struct
{
    #[OA\Property(type: 'string')]
    protected string $postalCode;

    #[OA\Property(type: 'string')]
    protected string $addressLine1;

    #[OA\Property(type: 'string')]
    protected string $adminArea2;

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): void
    {
        $this->addressLine1 = $addressLine1;
    }

    public function getAdminArea2(): string
    {
        return $this->adminArea2;
    }

    public function setAdminArea2(string $adminArea2): void
    {
        $this->adminArea2 = $adminArea2;
    }
}
