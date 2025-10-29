<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\AbstractContext;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\BusinessRuleErrorContext;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\DataErrorContext;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\InventoryIssueContext;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\PaymentErrorContext;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\PricingErrorContext;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\ShippingErrorContext;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_validation_issue',
    required: ['code', 'type', 'message']
)]
class ValidationIssue extends PayPalApiStruct
{
    public const CODE__INVENTORY_ISSUE = 'INVENTORY_ISSUE';
    public const CODE__PRICING_ERROR = 'PRICING_ERROR';
    public const CODE__SHIPPING_ERROR = 'SHIPPING_ERROR';
    public const CODE__PAYMENT_ERROR = 'PAYMENT_ERROR';
    public const CODE__DATA_ERROR = 'DATA_ERROR';
    public const CODE__BUSINESS_RULE_ERROR = 'BUSINESS_RULE_ERROR';

    public const TYPE__MISSING_FIELD = 'MISSING_FIELD';
    public const TYPE__INVALID_DATA = 'INVALID_DATA';
    public const TYPE__BUSINESS_RULE = 'BUSINESS_RULE';

    private const CODES = [
        self::CODE__INVENTORY_ISSUE,
        self::CODE__PRICING_ERROR,
        self::CODE__SHIPPING_ERROR,
        self::CODE__PAYMENT_ERROR,
        self::CODE__DATA_ERROR,
        self::CODE__BUSINESS_RULE_ERROR,
    ];

    private const TYPES = [self::TYPE__MISSING_FIELD, self::TYPE__INVALID_DATA, self::TYPE__BUSINESS_RULE];

    /**
     * Consolidated error category
     */
    #[OA\Property(
        type: 'string',
        enum: self::CODES
    )]
    protected string $code;

    /**
     * Type classification for error handling
     */
    #[OA\Property(
        type: 'string',
        enum: self::TYPES
    )]
    protected string $type;

    /**
     * Technical message for developers and logging
     */
    #[OA\Property(type: 'string')]
    protected string $message;

    /**
     * Customer-friendly message for end users
     */
    #[OA\Property(type: 'string')]
    protected ?string $userMessage = null;

    /**
     * Specific item ID if the issue is item-specific
     */
    #[OA\Property(type: 'string')]
    protected ?string $itemId = null;

    /**
     * Specific field name if the issue is field-specific
     */
    #[OA\Property(type: 'string')]
    protected ?string $field = null;

    /**
     * Category-specific context information
     */
    #[OA\Property(oneOf: [
        new OA\Schema(ref: InventoryIssueContext::class),
        new OA\Schema(ref: PricingErrorContext::class),
        new OA\Schema(ref: ShippingErrorContext::class),
        new OA\Schema(ref: PaymentErrorContext::class),
        new OA\Schema(ref: DataErrorContext::class),
        new OA\Schema(ref: BusinessRuleErrorContext::class),
    ])]
    protected ?AbstractContext $context = null;

    /**
     * Available actions to resolve this issue
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: ResolutionOption::class))]
    protected ResolutionOptionCollection $resolutionOptions;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        if (!\in_array($code, self::CODES, true)) {
            throw new \InvalidArgumentException('Invalid code');
        }

        $this->code = $code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        if (!\in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid type');
        }

        $this->type = $type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getUserMessage(): ?string
    {
        return $this->userMessage;
    }

    public function setUserMessage(?string $userMessage): void
    {
        $this->userMessage = $userMessage;
    }

    public function getItemId(): ?string
    {
        return $this->itemId;
    }

    public function setItemId(?string $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(?string $field): void
    {
        $this->field = $field;
    }

    public function getContext(): ?AbstractContext
    {
        return $this->context;
    }

    public function setContext(?AbstractContext $context): void
    {
        $this->context = $context;
    }

    public function getResolutionOptions(): ResolutionOptionCollection
    {
        return $this->resolutionOptions;
    }

    public function setResolutionOptions(ResolutionOptionCollection $resolutionOptions): void
    {
        $this->resolutionOptions = $resolutionOptions;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
