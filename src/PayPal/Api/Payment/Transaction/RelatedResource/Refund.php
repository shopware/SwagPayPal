<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;

class Refund extends RelatedResource
{
    /**
     * @var string
     */
    protected $saleId;

    /**
     * @var string
     */
    protected $captureId;

    public function getSaleId(): string
    {
        return $this->saleId;
    }

    public function setSaleId(string $saleId): void
    {
        $this->saleId = $saleId;
    }

    public function getCaptureId(): string
    {
        return $this->captureId;
    }

    public function setCaptureId(string $captureId): void
    {
        $this->captureId = $captureId;
    }
}
