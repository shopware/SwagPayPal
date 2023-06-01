<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\AdjudicationCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\CommunicationDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputeAmount;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputedTransactionCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputeOutcome;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\EvidenceCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\MessageCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\MoneyMovementCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Offer;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\PartnerActionCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\RefundDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\SupportingInfoCollection;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_item")
 */
#[Package('checkout')]
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
     * @OA\Property(type="string")
     */
    protected string $disputeId;

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $updateTime;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_disputed_transaction"}, nullable=true)
     */
    protected ?DisputedTransactionCollection $disputedTransactions = null;

    /**
     * @OA\Property(type="string")
     */
    protected string $reason;

    /**
     * @OA\Property(type="string")
     */
    protected string $status;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $disputeState = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected DisputeAmount $disputeAmount;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $externalReasonCode = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_dispute_outcome", nullable=true)
     */
    protected ?DisputeOutcome $disputeOutcome = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_adjudication"})
     */
    protected AdjudicationCollection $adjudications;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_money_movement"})
     */
    protected MoneyMovementCollection $moneyMovements;

    /**
     * @OA\Property(type="string")
     */
    protected string $disputeLifeCycleStage;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $disputeChannel = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_message"}, nullable=true)
     */
    protected ?MessageCollection $messages = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions")
     */
    protected Extensions $extensions;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_evidence"}, nullable=true)
     */
    protected ?EvidenceCollection $evidences = null;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $buyerResponseDueDate = null;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $sellerResponseDueDate = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_offer", nullable=true)
     */
    protected ?Offer $offer = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_refund_details", nullable=true)
     */
    protected ?RefundDetails $refundDetails = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_communication_details", nullable=true)
     */
    protected ?CommunicationDetails $communicationDetails = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_partner_action"}, nullable=true)
     */
    protected ?PartnerActionCollection $partnerActions = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_supporting_info"}, nullable=true)
     */
    protected ?SupportingInfoCollection $supportingInfo = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected LinkCollection $links;

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

    public function getDisputedTransactions(): ?DisputedTransactionCollection
    {
        return $this->disputedTransactions;
    }

    public function setDisputedTransactions(?DisputedTransactionCollection $disputedTransactions): void
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

    public function getAdjudications(): AdjudicationCollection
    {
        return $this->adjudications;
    }

    public function setAdjudications(AdjudicationCollection $adjudications): void
    {
        $this->adjudications = $adjudications;
    }

    public function getMoneyMovements(): MoneyMovementCollection
    {
        return $this->moneyMovements;
    }

    public function setMoneyMovements(MoneyMovementCollection $moneyMovements): void
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

    public function getMessages(): ?MessageCollection
    {
        return $this->messages;
    }

    public function setMessages(?MessageCollection $messages): void
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

    public function getEvidences(): ?EvidenceCollection
    {
        return $this->evidences;
    }

    public function setEvidences(?EvidenceCollection $evidences): void
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

    public function getPartnerActions(): ?PartnerActionCollection
    {
        return $this->partnerActions;
    }

    public function setPartnerActions(?PartnerActionCollection $partnerActions): void
    {
        $this->partnerActions = $partnerActions;
    }

    public function getSupportingInfo(): ?SupportingInfoCollection
    {
        return $this->supportingInfo;
    }

    public function setSupportingInfo(?SupportingInfoCollection $supportingInfo): void
    {
        $this->supportingInfo = $supportingInfo;
    }

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }
}
