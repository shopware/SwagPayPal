<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Adjudication;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\CommunicationDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputeAmount;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputedTransaction;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputeOutcome;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Evidence;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Message;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\MoneyMovement;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\PartnerAction;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\RefundDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\SupportingInfo;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_item")
 */
class Item extends PayPalApiStruct
{
    public const DISPUTE_STATE_REQUIRED_ACTION = 'REQUIRED_ACTION';
    public const DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION = 'REQUIRED_OTHER_PARTY_ACTION';
    public const DISPUTE_STATE_UNDER_PAYPAL_REVIEW = 'UNDER_PAYPAL_REVIEW';
    public const DISPUTE_STATE_RESOLVED = 'RESOLVED';
    public const DISPUTE_STATE_OPEN_INQUIRIES = 'OPEN_INQUIRIES';
    public const DISPUTE_STATE_APPEALABLE = 'APPEALABLE';

    public const DISPUTE_STATES = [
        self::DISPUTE_STATE_REQUIRED_ACTION,
        self::DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION,
        self::DISPUTE_STATE_UNDER_PAYPAL_REVIEW,
        self::DISPUTE_STATE_RESOLVED,
        self::DISPUTE_STATE_OPEN_INQUIRIES,
        self::DISPUTE_STATE_APPEALABLE,
    ];

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $disputeId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $createTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $updateTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var DisputedTransaction[]|null
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_disputed_transaction"}, nullable=true)
     */
    protected $disputedTransactions;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $reason;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $status;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $disputeState;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var DisputeAmount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected $disputeAmount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $externalReasonCode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var DisputeOutcome|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_dispute_outcome", nullable=true)
     */
    protected $disputeOutcome;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Adjudication[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_adjudication"})
     */
    protected $adjudications;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var MoneyMovement[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_money_movement"})
     */
    protected $moneyMovements;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $disputeLifeCycleStage;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $disputeChannel;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Message[]|null
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_message"}, nullable=true)
     */
    protected $messages;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Extensions
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions")
     */
    protected $extensions;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Evidence[]|null
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_evidence"}, nullable=true)
     */
    protected $evidences;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $buyerResponseDueDate;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $sellerResponseDueDate;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Offer|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_offer", nullable=true)
     */
    protected $offer;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var RefundDetails|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_refund_details", nullable=true)
     */
    protected $refundDetails;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var CommunicationDetails|null
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_communication_details", nullable=true)
     */
    protected $communicationDetails;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var PartnerAction[]|null
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_partner_action"}, nullable=true)
     */
    protected $partnerActions;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var SupportingInfo[]|null
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_supporting_info"}, nullable=true)
     */
    protected $supportingInfo;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Link[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected $links;

    public function getDisputeId(): string
    {
        return $this->disputeId;
    }

    public function setDisputeId(string $disputeId): void
    {
        $this->disputeId = $disputeId;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    /**
     * @return DisputedTransaction[]|null
     */
    public function getDisputedTransactions(): ?array
    {
        return $this->disputedTransactions;
    }

    /**
     * @param DisputedTransaction[]|null $disputedTransactions
     */
    public function setDisputedTransactions(?array $disputedTransactions): void
    {
        $this->disputedTransactions = $disputedTransactions;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getDisputeState(): ?string
    {
        return $this->disputeState;
    }

    public function setDisputeState(?string $disputeState): void
    {
        $this->disputeState = $disputeState;
    }

    public function getDisputeAmount(): DisputeAmount
    {
        return $this->disputeAmount;
    }

    public function setDisputeAmount(DisputeAmount $disputeAmount): void
    {
        $this->disputeAmount = $disputeAmount;
    }

    public function getExternalReasonCode(): ?string
    {
        return $this->externalReasonCode;
    }

    public function setExternalReasonCode(?string $externalReasonCode): void
    {
        $this->externalReasonCode = $externalReasonCode;
    }

    public function getDisputeOutcome(): ?DisputeOutcome
    {
        return $this->disputeOutcome;
    }

    public function setDisputeOutcome(?DisputeOutcome $disputeOutcome): void
    {
        $this->disputeOutcome = $disputeOutcome;
    }

    /**
     * @return Adjudication[]
     */
    public function getAdjudications(): array
    {
        return $this->adjudications;
    }

    /**
     * @param Adjudication[] $adjudications
     */
    public function setAdjudications(array $adjudications): void
    {
        $this->adjudications = $adjudications;
    }

    /**
     * @return MoneyMovement[]
     */
    public function getMoneyMovements(): array
    {
        return $this->moneyMovements;
    }

    /**
     * @param MoneyMovement[] $moneyMovements
     */
    public function setMoneyMovements(array $moneyMovements): void
    {
        $this->moneyMovements = $moneyMovements;
    }

    public function getDisputeLifeCycleStage(): string
    {
        return $this->disputeLifeCycleStage;
    }

    public function setDisputeLifeCycleStage(string $disputeLifeCycleStage): void
    {
        $this->disputeLifeCycleStage = $disputeLifeCycleStage;
    }

    public function getDisputeChannel(): ?string
    {
        return $this->disputeChannel;
    }

    public function setDisputeChannel(?string $disputeChannel): void
    {
        $this->disputeChannel = $disputeChannel;
    }

    /**
     * @return Message[]|null
     */
    public function getMessages(): ?array
    {
        return $this->messages;
    }

    /**
     * @param Message[]|null $messages
     */
    public function setMessages(?array $messages): void
    {
        $this->messages = $messages;
    }

    public function getExtensions(): Extensions
    {
        return $this->extensions;
    }

    public function setExtensions(Extensions $extensions): void
    {
        $this->extensions = $extensions;
    }

    /**
     * @return Evidence[]|null
     */
    public function getEvidences(): ?array
    {
        return $this->evidences;
    }

    /**
     * @param Evidence[]|null $evidences
     */
    public function setEvidences(?array $evidences): void
    {
        $this->evidences = $evidences;
    }

    public function getBuyerResponseDueDate(): ?string
    {
        return $this->buyerResponseDueDate;
    }

    public function setBuyerResponseDueDate(?string $buyerResponseDueDate): void
    {
        $this->buyerResponseDueDate = $buyerResponseDueDate;
    }

    public function getSellerResponseDueDate(): ?string
    {
        return $this->sellerResponseDueDate;
    }

    public function setSellerResponseDueDate(?string $sellerResponseDueDate): void
    {
        $this->sellerResponseDueDate = $sellerResponseDueDate;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): void
    {
        $this->offer = $offer;
    }

    public function getRefundDetails(): ?RefundDetails
    {
        return $this->refundDetails;
    }

    public function setRefundDetails(?RefundDetails $refundDetails): void
    {
        $this->refundDetails = $refundDetails;
    }

    public function getCommunicationDetails(): ?CommunicationDetails
    {
        return $this->communicationDetails;
    }

    public function setCommunicationDetails(?CommunicationDetails $communicationDetails): void
    {
        $this->communicationDetails = $communicationDetails;
    }

    /**
     * @return PartnerAction[]|null
     */
    public function getPartnerActions(): ?array
    {
        return $this->partnerActions;
    }

    /**
     * @param PartnerAction[]|null $partnerActions
     */
    public function setPartnerActions(?array $partnerActions): void
    {
        $this->partnerActions = $partnerActions;
    }

    /**
     * @return SupportingInfo[]|null
     */
    public function getSupportingInfo(): ?array
    {
        return $this->supportingInfo;
    }

    /**
     * @param SupportingInfo[]|null $supportingInfo
     */
    public function setSupportingInfo(?array $supportingInfo): void
    {
        $this->supportingInfo = $supportingInfo;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
