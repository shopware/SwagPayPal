<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DataAbstractionLayer\VaultToken;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DataAbstractionLayer\VaultTokenMapping\VaultTokenMappingDefinition;

#[Package('checkout')]
class VaultTokenEntity extends Entity
{
    use EntityIdTrait;

    protected string $customerId;

    protected string $paymentMethodId;

    protected string $token;

    protected ?string $tokenCustomer = null;

    protected string $identifier;

    protected ?CustomerEntity $customer = null;

    protected ?PaymentMethodEntity $paymentMethod = null;

    protected ?VaultTokenMappingDefinition $mainMapping = null;

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getTokenCustomer(): ?string
    {
        return $this->tokenCustomer;
    }

    public function setTokenCustomer(?string $tokenCustomer): void
    {
        $this->tokenCustomer = $tokenCustomer;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getPaymentMethod(): ?PaymentMethodEntity
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethodEntity $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getMainMapping(): ?VaultTokenMappingDefinition
    {
        return $this->mainMapping;
    }

    public function setMainMapping(?VaultTokenMappingDefinition $mainMapping): void
    {
        $this->mainMapping = $mainMapping;
    }
}
