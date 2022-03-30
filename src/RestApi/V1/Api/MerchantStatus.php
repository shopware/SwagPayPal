<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @deprecated tag:v6.0.0 - use MerchantIntegrations instead
 *
 * @OA\Schema(schema="swag_paypal_v1_merchant_status")
 */
class MerchantStatus extends PayPalApiStruct
{
    /**
     * @OA\Property(type="boolean")
     */
    protected bool $paymentsReceivable;

    /**
     * @OA\Property(type="string")
     */
    protected string $primaryEmail;

    /**
     * @OA\Property(type="boolean")
     */
    protected bool $primaryEmailConfirmed;

    public function getPaymentsReceivable(): bool
    {
        return $this->paymentsReceivable;
    }

    public function setPaymentsReceivable(bool $paymentsReceivable): void
    {
        $this->paymentsReceivable = $paymentsReceivable;
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
}
