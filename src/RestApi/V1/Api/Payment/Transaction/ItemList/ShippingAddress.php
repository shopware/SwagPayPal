<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Common\Address;

#[OA\Schema(schema: 'swag_paypal_v1_payment_transaction_item_list_shipping_address')]
#[Package('checkout')]
class ShippingAddress extends Address
{
    #[OA\Property(type: 'string')]
    protected string $recipientName;

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): void
    {
        $this->recipientName = $recipientName;
    }
}
