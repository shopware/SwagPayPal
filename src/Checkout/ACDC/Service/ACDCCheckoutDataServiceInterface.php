<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ACDC\Service;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ACDC\ACDCCheckoutFieldData;

interface ACDCCheckoutDataServiceInterface
{
    public function buildCheckoutData(SalesChannelContext $context, ?OrderEntity $order = null): ACDCCheckoutFieldData;
}
