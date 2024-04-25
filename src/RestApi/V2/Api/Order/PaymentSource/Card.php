<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\StoredCredential;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;

#[Package('checkout'), OA\Schema(schema: 'swag_paypal_v2_order_payment_source_card')]
class Card extends AbstractAPMPaymentSource implements VaultablePaymentSourceInterface
{
    #[OA\Property(type: 'string')]
    protected string $lastDigits;

    #[OA\Property(type: 'string')]
    protected string $brand;

    #[OA\Property(type: 'string')]
    protected string $type;

    #[OA\Property(type: 'string')]
    protected string $vaultId;

    #[OA\Property(ref: Address::class, nullable: true)]
    protected ?Address $billingAddress = null;

    #[OA\Property(ref: AuthenticationResult::class, nullable: true)]
    protected ?AuthenticationResult $authenticationResult = null;

    #[OA\Property(ref: Attributes::class, nullable: true)]
    protected ?Attributes $attributes = null;

    #[OA\Property(ref: StoredCredential::class, nullable: true)]
    protected ?StoredCredential $storedCredential = null;

    public function getLastDigits(): string
    {
        return $this->lastDigits;
    }

    public function setLastDigits(string $lastDigits): void
    {
        $this->lastDigits = $lastDigits;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getVaultId(): string
    {
        return $this->vaultId;
    }

    public function setVaultId(string $vaultId): void
    {
        $this->vaultId = $vaultId;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getAuthenticationResult(): ?AuthenticationResult
    {
        return $this->authenticationResult;
    }

    public function setAuthenticationResult(?AuthenticationResult $authenticationResult): void
    {
        $this->authenticationResult = $authenticationResult;
    }

    public function getAttributes(): ?Attributes
    {
        return $this->attributes;
    }

    public function setAttributes(?Attributes $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getStoredCredential(): ?StoredCredential
    {
        return $this->storedCredential;
    }

    public function setStoredCredential(?StoredCredential $storedCredential): void
    {
        $this->storedCredential = $storedCredential;
    }

    public function getVaultIdentifier(): string
    {
        return \sprintf('%s **%s', \ucfirst(\mb_strtolower($this->getBrand())), $this->getLastDigits());
    }

    public function jsonSerialize(): array
    {
        $values = parent::jsonSerialize();

        // this is inherited from APM payment source, but is actually not there
        unset($values['country_code']);

        return $values;
    }
}
