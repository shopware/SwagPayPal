<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Component\Patch;

use SwagPayPal\PayPal\Struct\Payment\Transactions\ItemList\ShippingAddress;

class PaymentAddressPatch implements PatchInterface
{
    public const PATH = '/transactions/0/item_list/shipping_address';

    /**
     * @var ShippingAddress
     */
    private $address;

    /**
     * @param ShippingAddress $address
     */
    public function __construct(ShippingAddress $address)
    {
        $this->address = $address;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation(): string
    {
        return self::OPERATION_ADD;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return self::PATH;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): array
    {
        return $this->address->toArray();
    }
}
