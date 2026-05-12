<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Storefront\Data\Struct\SEPACheckoutData;
use Swag\PayPal\Util\Lifecycle\Method\SEPAMethodData;

#[Package('checkout')]
class SEPACheckoutDataService extends AbstractCheckoutDataService
{
    public function buildCheckoutData(SalesChannelContext $context, ?Cart $cart = null, ?OrderEntity $order = null): ?SEPACheckoutData
    {
        return (new SEPACheckoutData())->assign($this->getBaseData($context, $order));
    }

    public function getMethodDataClass(): string
    {
        return SEPAMethodData::class;
    }
}
