<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Event;

use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Page;
use Swag\PayPal\Storefront\Data\CheckoutDataMethodInterface;
use Swag\PayPal\Storefront\Data\Struct\AbstractCheckoutData;

#[Package('checkout')]
class PayPalPageExtensionAddedEvent
{
    private Page $page;

    private CheckoutDataMethodInterface $checkoutMethod;

    private AbstractCheckoutData $checkoutData;

    public function __construct(
        Page $page,
        CheckoutDataMethodInterface $checkoutMethod,
        AbstractCheckoutData $checkoutData,
    ) {
        $this->page = $page;
        $this->checkoutMethod = $checkoutMethod;
        $this->checkoutData = $checkoutData;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getCheckoutMethod(): CheckoutDataMethodInterface
    {
        return $this->checkoutMethod;
    }

    public function getCheckoutData(): AbstractCheckoutData
    {
        return $this->checkoutData;
    }
}
