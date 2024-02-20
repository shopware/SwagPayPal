<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Common\LinkCollection;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_common_attributes_vault')]
#[Package('checkout')]
class Vault extends PayPalApiStruct
{
    public const STORE_IN_VAULT_ON_SUCCESS = 'ON_SUCCESS';
    public const USAGE_TYPE_MERCHANT = 'MERCHANT';

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $id = null;

    #[OA\Property(type: 'string')]
    protected string $storeInVault;

    #[OA\Property(type: 'string')]
    protected string $usageType;

    #[OA\Property(type: 'string')]
    protected string $status;

    #[OA\Property(type: 'string')]
    protected string $confirmPaymentToken;

    #[OA\Property(type: 'boolean')]
    protected bool $permitMultiplePaymentTokens = true;

    #[OA\Property(ref: Customer::class, nullable: true)]
    protected ?Customer $customer = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected LinkCollection $links;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getStoreInVault(): string
    {
        return $this->storeInVault;
    }

    public function setStoreInVault(string $storeInVault): void
    {
        $this->storeInVault = $storeInVault;
    }

    public function getUsageType(): string
    {
        return $this->usageType;
    }

    public function setUsageType(string $usageType): void
    {
        $this->usageType = $usageType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getConfirmPaymentToken(): string
    {
        return $this->confirmPaymentToken;
    }

    public function setConfirmPaymentToken(string $confirmPaymentToken): void
    {
        $this->confirmPaymentToken = $confirmPaymentToken;
    }

    public function isPermitMultiplePaymentTokens(): bool
    {
        return $this->permitMultiplePaymentTokens;
    }

    public function setPermitMultiplePaymentTokens(bool $permitMultiplePaymentTokens): void
    {
        $this->permitMultiplePaymentTokens = $permitMultiplePaymentTokens;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }
}
