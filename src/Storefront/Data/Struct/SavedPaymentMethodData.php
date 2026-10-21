<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;

/**
 * Lets a buyer with a saved PayPal wallet change its funding instrument for one order.
 */
#[Package('checkout')]
class SavedPaymentMethodData extends AbstractCheckoutData
{
    /**
     * Remembers the PayPal order the buyer approved with another funding instrument
     */
    protected string $editUrl;

    /**
     * PayPal order the buyer already approved with another funding instrument
     */
    protected ?string $approvedOrderId = null;

    public function getEditUrl(): string
    {
        return $this->editUrl;
    }

    public function setEditUrl(string $editUrl): void
    {
        $this->editUrl = $editUrl;
    }

    public function getApprovedOrderId(): ?string
    {
        return $this->approvedOrderId;
    }

    public function setApprovedOrderId(?string $approvedOrderId): void
    {
        $this->approvedOrderId = $approvedOrderId;
    }
}
