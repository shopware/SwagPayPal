<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\APM;

use Swag\PayPal\Checkout\APM\Service\AbstractAPMCheckoutDataService;

interface APMCheckoutMethodInterface
{
    public function getCheckoutDataService(): AbstractAPMCheckoutDataService;

    public function getCheckoutTemplateExtensionId(): string;

    /**
     * @return class-string
     */
    public function getHandler(): string;
}
