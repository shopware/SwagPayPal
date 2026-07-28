<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1\Context;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Struct\V1\Referral\BusinessHour;
use Swag\PayPal\AgenticCommerce\Struct\V1\Referral\BusinessHourCollection;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_context_business_rule_error_context')]
class BusinessRuleErrorContext extends AbstractContext
{
    public const ISSUE__MINIMUM_ORDER_NOT_MET = 'MINIMUM_ORDER_NOT_MET';
    public const ISSUE__MINIMUM_QUANTITY_NOT_MET = 'MINIMUM_QUANTITY_NOT_MET';
    public const ISSUE__MAXIMUM_QUANTITY_EXCEEDED = 'MAXIMUM_QUANTITY_EXCEEDED';
    public const ISSUE__CART_LIMIT_EXCEEDED = 'CART_LIMIT_EXCEEDED';
    public const ISSUE__CUSTOMER_ACCOUNT_SUSPENDED = 'CUSTOMER_ACCOUNT_SUSPENDED';
    public const ISSUE__PURCHASE_LIMIT_EXCEEDED = 'PURCHASE_LIMIT_EXCEEDED';
    public const ISSUE__BULK_ORDER_APPROVAL_REQUIRED = 'BULK_ORDER_APPROVAL_REQUIRED';
    public const ISSUE__STORE_TEMPORARILY_CLOSED = 'STORE_TEMPORARILY_CLOSED';
    public const ISSUE__AGE_RESTRICTED_PRODUCT = 'AGE_RESTRICTED_PRODUCT';
    public const ISSUE__LOYALTY_PROGRAM_VALIDATION_FAILED = 'LOYALTY_PROGRAM_VALIDATION_FAILED';
    public const ISSUE__BUSINESS_HOURS_RESTRICTION = 'BUSINESS_HOURS_RESTRICTION';
    public const ISSUE__PRODUCT_ARCHIVED = 'PRODUCT_ARCHIVED';

    /**
     * Current order amount
     */
    #[OA\Property(type: 'string')]
    protected ?string $currentAmount = null;

    /**
     * Required minimum amount
     */
    #[OA\Property(type: 'string')]
    protected ?string $requiredAmount = null;

    /**
     * Maximum allowed amount
     */
    #[OA\Property(type: 'string')]
    protected ?string $maximumAmount = null;

    /**
     * Amount needed to meet minimum
     */
    #[OA\Property(type: 'string')]
    protected ?string $remainingAmount = null;

    /**
     * Customer account status
     */
    #[OA\Property(type: 'string')]
    protected ?string $accountStatus = null;

    /**
     * Reason for account suspension
     */
    #[OA\Property(type: 'string')]
    protected ?string $suspensionReason = null;

    /**
     * Date of account suspension
     */
    #[OA\Property(type: 'string')]
    protected ?string $suspensionDate = null;

    /**
     * Monthly purchase limit
     */
    #[OA\Property(type: 'string')]
    protected ?string $monthlyLimit = null;

    /**
     * Current month purchase total
     */
    #[OA\Property(type: 'string')]
    protected ?string $currentMonthTotal = null;

    /**
     * When limits reset
     */
    #[OA\Property(type: 'string')]
    protected ?string $resetDate = null;

    /**
     * Total quantity in bulk order
     */
    #[OA\Property(type: 'integer')]
    protected ?int $totalQuantity = null;

    /**
     * Quantity requiring approval
     */
    #[OA\Property(type: 'integer')]
    protected ?int $approvalThreshold = null;

    /**
     * When maintenance ends
     */
    #[OA\Property(type: 'string')]
    protected ?string $maintenanceEndTime = null;

    /**
     * Current service status
     */
    #[OA\Property(type: 'string')]
    protected ?string $serviceStatus = null;

    /**
     * Seconds before retry recommended
     */
    #[OA\Property(type: 'integer')]
    protected ?int $retryAfter = null;

    /**
     * Support contact information
     */
    #[OA\Property(type: 'string')]
    protected ?string $contactInfo = null;

    /**
     * Items with restrictions
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $restrictedItems = null;

    /**
     * Required minimum age
     */
    #[OA\Property(type: 'integer')]
    protected ?int $ageRequirement = null;

    /**
     * Store business hours
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: BusinessHour::class))]
    protected BusinessHourCollection $businessHours;

    /**
     * Amount needed to meet minimum requirements
     */
    #[OA\Property(type: 'string')]
    protected ?string $shortageAmount = null;

    /**
     * Amount by which limit is exceeded
     */
    #[OA\Property(type: 'string')]
    protected ?string $exceedsBy = null;

    public function getCurrentAmount(): ?string
    {
        return $this->currentAmount;
    }

    public function setCurrentAmount(?string $currentAmount): void
    {
        $this->currentAmount = $currentAmount;
    }

    public function getRequiredAmount(): ?string
    {
        return $this->requiredAmount;
    }

    public function setRequiredAmount(?string $requiredAmount): void
    {
        $this->requiredAmount = $requiredAmount;
    }

    public function getMaximumAmount(): ?string
    {
        return $this->maximumAmount;
    }

    public function setMaximumAmount(?string $maximum_Amount): void
    {
        $this->maximumAmount = $maximum_Amount;
    }

    public function getRemainingAmount(): ?string
    {
        return $this->remainingAmount;
    }

    public function setRemainingAmount(?string $remainingAmount): void
    {
        $this->remainingAmount = $remainingAmount;
    }

    public function getAccountStatus(): ?string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(?string $accountStatus): void
    {
        $this->accountStatus = $accountStatus;
    }

    public function getSuspensionReason(): ?string
    {
        return $this->suspensionReason;
    }

    public function setSuspensionReason(?string $suspensionReason): void
    {
        $this->suspensionReason = $suspensionReason;
    }

    public function getSuspensionDate(): ?string
    {
        return $this->suspensionDate;
    }

    public function setSuspensionDate(?string $suspensionDate): void
    {
        $this->suspensionDate = $suspensionDate;
    }

    public function getMonthlyLimit(): ?string
    {
        return $this->monthlyLimit;
    }

    public function setMonthlyLimit(?string $monthlyLimit): void
    {
        $this->monthlyLimit = $monthlyLimit;
    }

    public function getCurrentMonthTotal(): ?string
    {
        return $this->currentMonthTotal;
    }

    public function setCurrentMonthTotal(?string $currentMonthTotal): void
    {
        $this->currentMonthTotal = $currentMonthTotal;
    }

    public function getResetDate(): ?string
    {
        return $this->resetDate;
    }

    public function setResetDate(?string $resetDate): void
    {
        $this->resetDate = $resetDate;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function setTotalQuantity(?int $totalQuantity): void
    {
        $this->totalQuantity = $totalQuantity;
    }

    public function getApprovalThreshold(): ?int
    {
        return $this->approvalThreshold;
    }

    public function setApprovalThreshold(?int $approvalThreshold): void
    {
        $this->approvalThreshold = $approvalThreshold;
    }

    public function getMaintenanceEndTime(): ?string
    {
        return $this->maintenanceEndTime;
    }

    public function setMaintenanceEndTime(?string $maintenanceEndTime): void
    {
        $this->maintenanceEndTime = $maintenanceEndTime;
    }

    public function getServiceStatus(): ?string
    {
        return $this->serviceStatus;
    }

    public function setServiceStatus(?string $serviceStatus): void
    {
        $this->serviceStatus = $serviceStatus;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function setRetryAfter(?int $retryAfter): void
    {
        $this->retryAfter = $retryAfter;
    }

    public function getContactInfo(): ?string
    {
        return $this->contactInfo;
    }

    public function setContactInfo(?string $contactInfo): void
    {
        $this->contactInfo = $contactInfo;
    }

    /**
     * @return ?string[]
     */
    public function getRestrictedItems(): ?array
    {
        return $this->restrictedItems;
    }

    /**
     * @param ?string[] $restrictedItems
     */
    public function setRestrictedItems(?array $restrictedItems): void
    {
        $this->restrictedItems = $restrictedItems;
    }

    public function addRestrictedItem(string $restrictedItem): void
    {
        $this->restrictedItems[] = $restrictedItem;
    }

    public function getAgeRequirement(): ?int
    {
        return $this->ageRequirement;
    }

    public function setAgeRequirement(?int $ageRequirement): void
    {
        $this->ageRequirement = $ageRequirement;
    }

    public function getBusinessHours(): BusinessHourCollection
    {
        return $this->businessHours;
    }

    public function setBusinessHours(BusinessHourCollection $businessHours): void
    {
        $this->businessHours = $businessHours;
    }

    public function getShortageAmount(): ?string
    {
        return $this->shortageAmount;
    }

    public function setShortageAmount(?string $shortageAmount): void
    {
        $this->shortageAmount = $shortageAmount;
    }

    public function getExceedsBy(): ?string
    {
        return $this->exceedsBy;
    }

    public function setExceedsBy(?string $exceedsBy): void
    {
        $this->exceedsBy = $exceedsBy;
    }

    protected static function getSpecificIssues(): array
    {
        return [
            self::ISSUE__MINIMUM_ORDER_NOT_MET,
            self::ISSUE__MINIMUM_QUANTITY_NOT_MET,
            self::ISSUE__MAXIMUM_QUANTITY_EXCEEDED,
            self::ISSUE__CART_LIMIT_EXCEEDED,
            self::ISSUE__CUSTOMER_ACCOUNT_SUSPENDED,
            self::ISSUE__PURCHASE_LIMIT_EXCEEDED,
            self::ISSUE__BULK_ORDER_APPROVAL_REQUIRED,
            self::ISSUE__STORE_TEMPORARILY_CLOSED,
            self::ISSUE__AGE_RESTRICTED_PRODUCT,
            self::ISSUE__LOYALTY_PROGRAM_VALIDATION_FAILED,
            self::ISSUE__BUSINESS_HOURS_RESTRICTION,
            self::ISSUE__PRODUCT_ARCHIVED,
        ];
    }
}
