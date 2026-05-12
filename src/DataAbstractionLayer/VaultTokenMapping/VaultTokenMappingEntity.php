<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DataAbstractionLayer\VaultTokenMapping;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;

#[Package('checkout')]
class VaultTokenMappingEntity extends Entity
{
    use EntityIdTrait;

    private string $customerId;

    private string $paymentMethodId;

    private string $tokenId;

    private ?CustomerEntity $customer = null;

    private ?PaymentMethodEntity $paymentMethod = null;

    private ?VaultTokenEntity $token = null;

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

    public function getTokenId(): string
    {
        return $this->tokenId;
    }

    public function setTokenId(string $tokenId): void
    {
        $this->tokenId = $tokenId;
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

    public function getToken(): ?VaultTokenEntity
    {
        return $this->token;
    }

    public function setToken(VaultTokenEntity $token): void
    {
        $this->token = $token;
    }
}
