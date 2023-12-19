<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture\SellerReceivableBreakdown;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Common\SellerProtection;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_capture")
 */
#[Package('checkout')]
class Capture extends Payment
{
    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $invoiceId = null;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $noteToPayer = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_capture_seller_protection")
     */
    protected SellerProtection $sellerProtection;

    /**
     * @OA\Property(type="boolean")
     */
    protected bool $finalCapture;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_capture_seller_receivable_breakdown")
     */
    protected SellerReceivableBreakdown $sellerReceivableBreakdown;

    /**
     * @OA\Property(type="string")
     */
    protected string $disbursementMode;

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

    public function getSellerProtection(): SellerProtection
    {
        return $this->sellerProtection;
    }

    public function setSellerProtection(SellerProtection $sellerProtection): void
    {
        $this->sellerProtection = $sellerProtection;
    }

    public function isFinalCapture(): bool
    {
        return $this->finalCapture;
    }

    public function setFinalCapture(bool $finalCapture): void
    {
        $this->finalCapture = $finalCapture;
    }

    public function getSellerReceivableBreakdown(): SellerReceivableBreakdown
    {
        return $this->sellerReceivableBreakdown;
    }

    public function setSellerReceivableBreakdown(SellerReceivableBreakdown $sellerReceivableBreakdown): void
    {
        $this->sellerReceivableBreakdown = $sellerReceivableBreakdown;
    }

    public function getDisbursementMode(): string
    {
        return $this->disbursementMode;
    }

    public function setDisbursementMode(string $disbursementMode): void
    {
        $this->disbursementMode = $disbursementMode;
    }
}
