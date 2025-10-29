<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\AgeVerificationValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\AllergyInformationValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\CustomEngravingTextValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\CustomSizingInfoValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\DeliveryDatePreferenceValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\DeliveryInstructionsValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\GiftMessageValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\GiftRecipientEmailValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\GiftRecipientNameValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\PrivacyConsentValue;
use Swag\PayPal\AgentCommerce\Struct\V1\Value\TermsAcceptanceValue;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 *
 * PayPal-controlled checkout field for buyer data collection with structured values and validation.
 *
 * Field Lifecycle:
 *
 * PENDING: Field needs customer input
 * COMPLETED: Valid value provided and accepted
 * REJECTED: Invalid/unacceptable value provided
 * ERROR: System error during processing
 *
 * Structured Values: Each field type has a specific value schema based on its requirements. Age verification uses boolean confirmation, text fields use strings, etc.
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_checkout_field',
    required: ['type', 'status']
)]
class CheckoutField extends PayPalApiStruct
{
    public const TYPE__AGE_VERIFICATION_18_PLUS = 'AGE_VERIFICATION_18_PLUS';
    public const TYPE__AGE_VERIFICATION_21_PLUS = 'AGE_VERIFICATION_21_PLUS';
    public const TYPE__GIFT_RECIPIENT_EMAIL = 'GIFT_RECIPIENT_EMAIL';
    public const TYPE__GIFT_RECIPIENT_NAME = 'GIFT_RECIPIENT_NAME';
    public const TYPE__GIFT_MESSAGE = 'GIFT_MESSAGE';
    public const TYPE__DELIVERY_INSTRUCTIONS = 'DELIVERY_INSTRUCTIONS';
    public const TYPE__DELIVERY_DATE_PREFERENCE = 'DELIVERY_DATE_PREFERENCE';
    public const TYPE__ALLERGY_INFORMATION = 'ALLERGY_INFORMATION';
    public const TYPE__CUSTOM_ENGRAVING_TEXT = 'CUSTOM_ENGRAVING_TEXT';
    public const TYPE__CUSTOM_SIZING_INFO = 'CUSTOM_SIZING_INFO';
    public const TYPE__TERMS_ACCEPTANCE = 'TERMS_ACCEPTANCE';
    public const TYPE__PRIVACY_CONSENT = 'PRIVACY_CONSENT';

    public const STATUS__PENDING = 'PENDING';
    public const STATUS__COMPLETED = 'COMPLETED';
    public const STATUS__REJECTED = 'REJECTED';
    public const STATUS__ERROR = 'ERROR';

    private const TYPES = [
        self::TYPE__AGE_VERIFICATION_18_PLUS,
        self::TYPE__AGE_VERIFICATION_21_PLUS,
        self::TYPE__GIFT_RECIPIENT_EMAIL,
        self::TYPE__GIFT_RECIPIENT_NAME,
        self::TYPE__GIFT_MESSAGE,
        self::TYPE__DELIVERY_INSTRUCTIONS,
        self::TYPE__DELIVERY_DATE_PREFERENCE,
        self::TYPE__ALLERGY_INFORMATION,
        self::TYPE__CUSTOM_ENGRAVING_TEXT,
        self::TYPE__CUSTOM_SIZING_INFO,
        self::TYPE__TERMS_ACCEPTANCE,
        self::TYPE__PRIVACY_CONSENT,
    ];

    private const STATUSES = [
        self::STATUS__PENDING,
        self::STATUS__COMPLETED,
        self::STATUS__REJECTED,
        self::STATUS__ERROR,
    ];

    /**
     * PayPal-approved checkout field type
     */
    #[OA\Property(
        type: 'string',
        enum: self::TYPES,
    )]
    protected string $type;

    /**
     * Field completion and validation status:
     *
     * PENDING: Field needs customer input
     *
     * Initial state when field is required
     * AI agent should collect this information
     * value field is null or empty
     *
     * COMPLETED: Valid value provided and accepted
     *
     * Customer provided acceptable input
     * Value passes all validation rules
     * Cart can proceed with this field resolved
     *
     * REJECTED: Invalid or unacceptable value provided
     *
     * Customer provided input that doesn't meet requirements
     * validation_issue explains the specific problem
     * AI agent should request corrected input
     *
     * ERROR: System error during processing
     *
     * Technical failure in field processing
     * Should retry or escalate to support
     * Not caused by customer input
     */
    #[OA\Property(
        type: 'string',
        enum: self::STATUSES
    )]
    protected string $status;

    /**
     * Structured value based on field type. Each checkout field type has a specific value schema.
     * Use oneOf to validate against the appropriate structure for the field type.
     */
    #[OA\Property(oneOf: [
        new OA\Schema(ref: AgeVerificationValue::class),
        new OA\Schema(ref: GiftRecipientEmailValue::class),
        new OA\Schema(ref: GiftRecipientNameValue::class),
        new OA\Schema(ref: GiftMessageValue::class),
        new OA\Schema(ref: DeliveryInstructionsValue::class),
        new OA\Schema(ref: DeliveryDatePreferenceValue::class),
        new OA\Schema(ref: AllergyInformationValue::class),
        new OA\Schema(ref: CustomEngravingTextValue::class),
        new OA\Schema(ref: CustomSizingInfoValue::class),
        new OA\Schema(ref: TermsAcceptanceValue::class),
        new OA\Schema(ref: PrivacyConsentValue::class),
    ])]
    protected PayPalApiStruct $value;

    /**
     * Additional context and metadata for the checkout field.
     * This is a flexible object that can contain any field-specific information needed for validation, display, or processing.
     * The structure varies based on the field type.
     */
    #[OA\Property(type: 'mixed')]
    protected mixed $context = null;

    #[OA\Property(ref: ValidationIssue::class)]
    protected ?ValidationIssue $validationIssue = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        if (!\in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException(\sprintf('Type "%s" is not valid.', $type));
        }

        $this->type = $type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!\in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException(\sprintf('Status "%s" is not valid.', $status));
        }

        $this->status = $status;
    }

    public function getValue(): PayPalApiStruct
    {
        return $this->value;
    }

    public function setValue(PayPalApiStruct $value): void
    {
        $this->value = $value;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    public function setContext(mixed $context): void
    {
        $this->context = $context;
    }

    public function getValidationIssue(): ?ValidationIssue
    {
        return $this->validationIssue;
    }

    public function setValidationIssue(?ValidationIssue $validationIssue): void
    {
        $this->validationIssue = $validationIssue;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
