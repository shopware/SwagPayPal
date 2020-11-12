<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund\SellerPayableBreakdown;

class Refund extends Payment
{
    /**
     * @var string|null
     */
    protected $invoiceId;

    /**
     * @var string|null
     */
    protected $noteToPayer;

    /**
     * @var SellerPayableBreakdown
     */
    protected $sellerPayableBreakdown;

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getNoteToPayer(): ?string
    {
        return $this->noteToPayer;
    }

    public function setNoteToPayer(?string $noteToPayer): void
    {
        $this->noteToPayer = $noteToPayer;
    }

    public function getSellerPayableBreakdown(): SellerPayableBreakdown
    {
        return $this->sellerPayableBreakdown;
    }

    public function setSellerPayableBreakdown(SellerPayableBreakdown $sellerPayableBreakdown): void
    {
        $this->sellerPayableBreakdown = $sellerPayableBreakdown;
    }
}
