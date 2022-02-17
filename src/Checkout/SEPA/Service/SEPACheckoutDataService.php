<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SEPA\Service;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\APM\APMCheckoutData;
use Swag\PayPal\Checkout\APM\Service\AbstractAPMCheckoutDataService;
use Swag\PayPal\Checkout\SEPA\SEPACheckoutData;
use Swag\PayPal\Util\Lifecycle\Method\SEPAMethodData;

class SEPACheckoutDataService extends AbstractAPMCheckoutDataService
{
    public function buildCheckoutData(SalesChannelContext $context, ?OrderEntity $order = null): APMCheckoutData
    {
        return (new SEPACheckoutData())->assign($this->getBaseData($context, $order));
    }

    public function getMethodDataClass(): string
    {
        return SEPAMethodData::class;
    }
}
