<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Service;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class ReturnToken
{
    public const TARGET_CHECKOUT_CONFIRM = 'checkout-confirm';
    public const TARGET_ACCOUNT_ORDER_EDIT = 'account-order-edit';

    public const TARGETS = [
        self::TARGET_CHECKOUT_CONFIRM,
        self::TARGET_ACCOUNT_ORDER_EDIT,
    ];

    public function __construct(
        private readonly string $contextToken,
        private readonly string $salesChannelId,
        private readonly string $returnTarget,
        private readonly ?string $orderId = null,
    ) {
    }

    public function getContextToken(): string
    {
        return $this->contextToken;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getReturnTarget(): string
    {
        return $this->returnTarget;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }
}
