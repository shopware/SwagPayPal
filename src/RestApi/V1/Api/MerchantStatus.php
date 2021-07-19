<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use Swag\PayPal\RestApi\PayPalApiStruct;

class MerchantStatus extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     */
    protected $paymentsReceivable;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $primaryEmail;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     */
    protected $primaryEmailConfirmed;

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
