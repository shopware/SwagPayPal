<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\MetaData;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_resolution_option',
    required: ['action', 'label']
)]
class ResolutionOption extends Struct
{
    public const ACTION__REDIRECT_TO_MERCHANT = 'REDIRECT_TO_MERCHANT';
    public const ACTION__MODIFY_CART = 'MODIFY_CART';
    public const ACTION__ACCEPT_NEW_PRICE = 'ACCEPT_NEW_PRICE';
    public const ACTION__ACCEPT_BACK_ORDER = 'ACCEPT_BACK_ORDER';
    public const ACTION__SUGGEST_ALTERNATIVE = 'SUGGEST_ALTERNATIVE';
    public const ACTION__REMOVE_ITEM = 'REMOVE_ITEM';
    public const ACTION__UPDATE_ADDRESS = 'UPDATE_ADDRESS';
    public const ACTION__PROVIDE_MISSING_FIELD = 'PROVIDE_MISSING_FIELD';
    public const ACTION__USE_DIFFERENT_PAYMENT = 'USE_DIFFERENT_PAYMENT';
    public const ACTION__SPLIT_ORDER = 'SPLIT_ORDER';
    public const ACTION__CONTACT_SUPPORT = 'CONTACT_SUPPORT';
    public const ACTION__RETRY_LATER = 'RETRY_LATER';
    public const ACTION__REQUEST_APPROVAL = 'REQUEST_APPROVAL';
    public const ACTION__WAIT_FOR_RESTOCK = 'WAIT_FOR_RESTOCK';
    public const ACTION__USE_DIFFERENT_CURRENCY = 'USE_DIFFERENT_CURRENCY';
    public const ACTION__ACCEPT_PRE_ORDER = 'ACCEPT_PRE_ORDER';
    public const ACTION__UPDATE_SHIPPING_METHOD = 'UPDATE_SHIPPING_METHOD';
    public const ACTION__ACCEPT_TERMS = 'ACCEPT_TERMS';
    public const ACTION__VERIFY_ACCOUNT = 'VERIFY_ACCOUNT';
    public const ACTION__APPLY_DIFFERENT_COUPON = 'APPLY_DIFFERENT_COUPON';
    public const ACTION__REMOVE_COUPON = 'REMOVE_COUPON';
    public const ACTION__CHOOSE_DIFFERENT_VARIANT = 'CHOOSE_DIFFERENT_VARIANT';

    public const ACTIONS = [
        self::ACTION__REDIRECT_TO_MERCHANT,
        self::ACTION__MODIFY_CART,
        self::ACTION__ACCEPT_NEW_PRICE,
        self::ACTION__ACCEPT_BACK_ORDER,
        self::ACTION__SUGGEST_ALTERNATIVE,
        self::ACTION__REMOVE_ITEM,
        self::ACTION__UPDATE_ADDRESS,
        self::ACTION__PROVIDE_MISSING_FIELD,
        self::ACTION__USE_DIFFERENT_PAYMENT,
        self::ACTION__SPLIT_ORDER,
        self::ACTION__CONTACT_SUPPORT,
        self::ACTION__RETRY_LATER,
        self::ACTION__REQUEST_APPROVAL,
        self::ACTION__WAIT_FOR_RESTOCK,
        self::ACTION__USE_DIFFERENT_CURRENCY,
        self::ACTION__ACCEPT_PRE_ORDER,
        self::ACTION__UPDATE_SHIPPING_METHOD,
        self::ACTION__ACCEPT_TERMS,
        self::ACTION__VERIFY_ACCOUNT,
        self::ACTION__APPLY_DIFFERENT_COUPON,
        self::ACTION__REMOVE_COUPON,
        self::ACTION__CHOOSE_DIFFERENT_VARIANT,
    ];

    /**
     * Machine-readable action identifier
     */
    #[OA\Property(
        type: 'string',
        enum: self::ACTIONS,
    )]
    protected string $action;

    /**
     * Human-readable action label
     */
    #[OA\Property(type: 'string')]
    protected string $label;

    /**
     * URL to redirect to for resolution
     */
    #[OA\Property(type: 'string')]
    protected ?string $url = null;

    /**
     * Additional action metadata
     */
    #[OA\Property(ref: MetaData::class)]
    protected MetaData $metadata;

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        if (!\in_array($action, self::ACTIONS, true)) {
            throw new \InvalidArgumentException(\sprintf('Action "%s" is not valid.', $action));
        }

        $this->action = $action;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getMetadata(): MetaData
    {
        return $this->metadata;
    }

    public function setMetadata(MetaData $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }

    public function isset(string $propertyName): bool
    {
        return isset($this->{$propertyName});
    }
}
