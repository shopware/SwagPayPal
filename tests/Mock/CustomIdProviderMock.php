<?php
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Swag\PayPal\OrdersApi\Builder\Util\CustomIdProvider;

/**
 * @internal
 */
class CustomIdProviderMock extends CustomIdProvider
{
    public function __construct()
    {
        $this->shopwareVersion = 'shopwareVersion';
        $this->pluginVersion = 'pluginVersion';
    }
}
