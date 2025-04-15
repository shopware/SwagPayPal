<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;

#[Package('checkout')]
interface ExpressCheckoutDataServiceInterface
{
    /**
     * @deprecated tag:v10.0.0 - reason:new-optional-parameter - a ShopwareSalesChannelEvent that defaults to null will be added
     */
    public function buildExpressCheckoutButtonData(SalesChannelContext $salesChannelContext, bool $addProductToCart = false): ?ExpressCheckoutButtonData;
}
