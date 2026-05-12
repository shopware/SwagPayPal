<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\TokenResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractExpressCreateOrderRoute
{
    abstract public function getDecorated(): AbstractExpressCreateOrderRoute;

    abstract public function createPayPalOrder(Request $request, SalesChannelContext $salesChannelContext): TokenResponse;
}
