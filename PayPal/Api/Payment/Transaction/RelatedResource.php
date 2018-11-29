<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment\Transaction;

use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource\Sale;
use SwagPayPal\PayPal\Api\PayPalStruct;

class RelatedResource extends PayPalStruct
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var Sale
     */
    private $sale;

    public function getSale(): Sale
    {
        return $this->sale;
    }

    protected function setType(string $type): void
    {
        $this->type = $type;
    }

    protected function setSale(Sale $sale): void
    {
        $this->sale = $sale;
    }
}
