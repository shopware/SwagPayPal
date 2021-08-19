<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v2_referral_legal_consent")
 */
class LegalConsent extends PayPalApiStruct
{
    public const CONSENT_TYPE_SHARE_DATA = 'SHARE_DATA_CONSENT';

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string", default=Swag\PayPal\RestApi\V2\Api\Referral\LegalConsent::CONSENT_TYPE_SHARE_DATA)
     */
    protected $type = self::CONSENT_TYPE_SHARE_DATA;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     * @OA\Property(type="boolean")
     */
    protected $granted = true;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getGranted(): bool
    {
        return $this->granted;
    }

    public function setGranted(bool $granted): void
    {
        $this->granted = $granted;
    }
}
