<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Struct\V1\Referral\Recipient;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_gift_options')]
class GiftOptions extends PayPalApiStruct
{
    /**
     * Whether this is a gift
     */
    #[OA\Property(type: 'boolean')]
    protected bool $isGift = false;

    /**
     * Gift recipient information
     */
    #[OA\Property(ref: Recipient::class)]
    protected ?Recipient $recipient = null;

    /**
     * Scheduled delivery date in RFC3339 format. Seconds are required while fractional seconds are optional.
     *
     * example: 2024-12-25T09:00:00Z
     */
    #[OA\Property(
        type: 'string',
        maxLength: 64,
        minLength: 20,
        pattern: '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])[T,t]([0-1][0-9]|2[0-3]):[0-5][0-9]:([0-5][0-9]|60)([.][0-9]+)?([Zz]|[+-][0-9]{2}:[0-9]{2})$'
    )]
    protected ?string $deliveryDate = null;

    /**
     * Name of gift sender
     */
    #[OA\Property(type: 'string')]
    protected ?string $senderName = null;

    /**
     * Personal message (max 500 characters)
     */
    #[OA\Property(
        type: 'string',
        maxLength: 500,
    )]
    protected ?string $giftMessage = null;

    /**
     * Whether to include gift wrapping
     */
    #[OA\Property(type: 'boolean')]
    protected ?bool $giftWrap = null;

    public function isGift(): bool
    {
        return $this->isGift;
    }

    public function setIsGift(bool $isGift): void
    {
        $this->isGift = $isGift;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    public function setRecipient(?Recipient $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getDeliveryDate(): ?string
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?string $deliveryDate): void
    {
        $this->deliveryDate = $deliveryDate;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): void
    {
        $this->senderName = $senderName;
    }

    public function getGiftMessage(): ?string
    {
        return $this->giftMessage;
    }

    public function setGiftMessage(?string $giftMessage): void
    {
        $this->giftMessage = $giftMessage;
    }

    public function getGiftWrap(): ?bool
    {
        return $this->giftWrap;
    }

    public function setGiftWrap(?bool $giftWrap): void
    {
        $this->giftWrap = $giftWrap;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
