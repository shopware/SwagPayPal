<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel\Response;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;

/**
 * @extends StoreApiResponse<ArrayStruct<array<string, mixed>>>
 */
#[Package('checkout')]
final class AgentCartResponse extends StoreApiResponse
{
    public function __construct(
        protected PayPalCart $cart,
    ) {
        parent::__construct(
            new ArrayStruct($this->cart->jsonSerialize())
        );
    }
}
