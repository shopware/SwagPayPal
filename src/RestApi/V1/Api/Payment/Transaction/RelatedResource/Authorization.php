<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

class Authorization extends RelatedResource
{
    /**
     * @var string
     */
    protected $reasonCode;

    /**
     * @var string
     */
    protected $validUntil;

    public function getReasonCode(): string
    {
        return $this->reasonCode;
    }

    public function setReasonCode(string $reasonCode): void
    {
        $this->reasonCode = $reasonCode;
    }

    public function getValidUntil(): string
    {
        return $this->validUntil;
    }

    public function setValidUntil(string $validUntil): void
    {
        $this->validUntil = $validUntil;
    }
}
