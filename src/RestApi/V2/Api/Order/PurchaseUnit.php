<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ItemCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payee;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit')]
#[Package('checkout')]
class PurchaseUnit extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $referenceId;

    #[OA\Property(ref: Amount::class)]
    protected Amount $amount;

    #[OA\Property(ref: Payee::class)]
    protected Payee $payee;

    #[OA\Property(type: 'string')]
    protected string $description;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $customId = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $invoiceId = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Item::class), nullable: true)]
    protected ?ItemCollection $items = null;

    #[OA\Property(ref: Shipping::class)]
    protected Shipping $shipping;

    #[OA\Property(ref: Payments::class, nullable: true)]
    protected ?Payments $payments = null;

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function setReferenceId(string $referenceId): void
    {
        $this->referenceId = $referenceId;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getPayee(): Payee
    {
        return $this->payee;
    }

    public function setPayee(Payee $payee): void
    {
        $this->payee = $payee;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function setCustomId(?string $customId): void
    {
        $this->customId = $customId;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getItems(): ?ItemCollection
    {
        return $this->items;
    }

    public function setItems(?ItemCollection $items): void
    {
        $this->items = $items;
    }

    public function getShipping(): Shipping
    {
        return $this->shipping;
    }

    public function setShipping(Shipping $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getPayments(): ?Payments
    {
        return $this->payments;
    }

    public function setPayments(Payments $payments): void
    {
        $this->payments = $payments;
    }
}
