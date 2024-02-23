<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund\SellerPayableBreakdown;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_payments_refund')]
#[Package('checkout')]
class Refund extends Payment
{
    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $invoiceId = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $noteToPayer = null;

    #[OA\Property(ref: SellerPayableBreakdown::class)]
    protected SellerPayableBreakdown $sellerPayableBreakdown;

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setInvoiceId(?string $invoiceId): void
    {
        if ($invoiceId !== null && \mb_strlen($invoiceId) > self::MAX_LENGTH_INVOICE_ID) {
            throw new \LengthException(
                \sprintf('%s::$invoiceId must not be longer than %s characters', self::class, self::MAX_LENGTH_INVOICE_ID)
            );
        }

        $this->invoiceId = $invoiceId;
    }

    public function getNoteToPayer(): ?string
    {
        return $this->noteToPayer;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setNoteToPayer(?string $noteToPayer): void
    {
        if ($noteToPayer !== null && \mb_strlen($noteToPayer) > self::MAX_LENGTH_NOTE_TO_PAYER) {
            throw new \LengthException(
                \sprintf('%s::$invoiceId must not be longer than %s characters', self::class, self::MAX_LENGTH_NOTE_TO_PAYER)
            );
        }

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
