<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral;

use Swag\PayPal\RestApi\PayPalApiStruct;

class LegalConsent extends PayPalApiStruct
{
    public const CONSENT_TYPE_SHARE_DATA = 'SHARE_DATA_CONSENT';

    /**
     * @var string
     */
    protected $type = self::CONSENT_TYPE_SHARE_DATA;

    /**
     * @var bool
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
