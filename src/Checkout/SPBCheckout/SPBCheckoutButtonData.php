<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Storefront\Data\Struct\AbstractCheckoutData;

#[Package('checkout')]
class SPBCheckoutButtonData extends AbstractCheckoutData
{
    protected string $buttonColor;

    protected bool $useAlternativePaymentMethods;

    /**
     * @var string[]
     */
    protected array $disabledAlternativePaymentMethods;

    protected bool $showPayLater;

    public function getButtonColor(): string
    {
        return $this->buttonColor;
    }

    public function getUseAlternativePaymentMethods(): bool
    {
        return $this->useAlternativePaymentMethods;
    }

    /**
     * @return string[]
     */
    public function getDisabledAlternativePaymentMethods(): array
    {
        return $this->disabledAlternativePaymentMethods;
    }

    /**
     * @param string[] $disabledAlternativePaymentMethods
     */
    public function setDisabledAlternativePaymentMethods(array $disabledAlternativePaymentMethods): void
    {
        $this->disabledAlternativePaymentMethods = $disabledAlternativePaymentMethods;
    }

    public function getShowPayLater(): bool
    {
        return $this->showPayLater;
    }
}
