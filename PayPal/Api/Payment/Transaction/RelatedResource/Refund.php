<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource;

class Refund extends RelatedResource
{
    /**
     * @var string
     */
    private $saleId;

    protected function setSaleId(string $saleId): void
    {
        $this->saleId = $saleId;
    }
}
