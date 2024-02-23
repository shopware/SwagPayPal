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

#[OA\Schema(schema: 'swag_paypal_v2_referral_legal_consent')]
#[Package('checkout')]
class LegalConsent extends PayPalApiStruct
{
    public const CONSENT_TYPE_SHARE_DATA = 'SHARE_DATA_CONSENT';

    #[OA\Property(type: 'string', default: self::CONSENT_TYPE_SHARE_DATA)]
    protected string $type = self::CONSENT_TYPE_SHARE_DATA;

    #[OA\Property(type: 'boolean')]
    protected bool $granted = true;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isGranted(): bool
    {
        return $this->granted;
    }

    public function setGranted(bool $granted): void
    {
        $this->granted = $granted;
    }
}
