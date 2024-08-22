<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;

#[Package('checkout')]
interface CheckoutDataMethodInterface
{
    public function getCheckoutDataService(): AbstractCheckoutDataService;

    public function getCheckoutTemplateExtensionId(): string;

    /**
     * @return class-string
     */
    public function getHandler(): string;
}
