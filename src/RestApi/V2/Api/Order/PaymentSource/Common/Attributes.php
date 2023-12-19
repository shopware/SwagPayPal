<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Customer;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Vault;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source_attributes")
 */
#[Package('checkout')]
class Attributes extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_attributes_vault")
     */
    protected ?Vault $vault = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_attributes_customer")
     */
    protected ?Customer $customer = null;

    public function getVault(): ?Vault
    {
        return $this->vault;
    }

    public function setVault(?Vault $vault): void
    {
        $this->vault = $vault;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
    }
}
