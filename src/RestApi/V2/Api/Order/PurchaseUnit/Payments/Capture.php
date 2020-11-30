<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture\SellerProtection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture\SellerReceivableBreakdown;

class Capture extends Payment
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
     * @var SellerProtection
     */
    protected $sellerProtection;

    /**
     * @var bool
     */
    protected $finalCapture;

    /**
     * @var SellerReceivableBreakdown
     */
    protected $sellerReceivableBreakdown;

    /**
     * @var string
     */
    protected $disbursementMode;

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setInvoiceId(?string $invoiceId): void
    {
        if ($invoiceId !== null && \strlen($invoiceId) > self::MAX_LENGTH_INVOICE_ID) {
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
        if ($noteToPayer !== null && \strlen($noteToPayer) > self::MAX_LENGTH_NOTE_TO_PAYER) {
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
