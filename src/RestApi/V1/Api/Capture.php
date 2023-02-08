<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Capture\Amount;
use Swag\PayPal\RestApi\V1\Api\Capture\Link;
use Swag\PayPal\RestApi\V1\Api\Capture\TransactionFee;

/**
 * @OA\Schema(schema="swag_paypal_v1_capture")
 */
class Capture extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_amount")
     */
    protected Amount $amount;

    /**
     * @OA\Property(type="boolean")
     */
    protected bool $isFinalCapture;

    /**
     * @OA\Property(type="string")
     */
    protected string $id;

    /**
     * @OA\Property(type="string")
     */
    protected string $state;

    /**
     * @OA\Property(type="string")
     */
    protected string $reasonCode;

    /**
     * @OA\Property(type="string")
     */
    protected string $parentPayment;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_value")
     */
    protected TransactionFee $transactionFee;

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $updateTime;

    /**
     * @var Link[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected array $links;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function isFinalCapture(): bool
    {
        return $this->isFinalCapture;
    }

    public function setIsFinalCapture(bool $isFinalCapture): void
    {
        $this->isFinalCapture = $isFinalCapture;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getReasonCode(): string
    {
        return $this->reasonCode;
    }

    public function setReasonCode(string $reasonCode): void
    {
        $this->reasonCode = $reasonCode;
    }

    public function getParentPayment(): string
    {
        return $this->parentPayment;
    }

    public function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    public function getTransactionFee(): TransactionFee
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(TransactionFee $transactionFee): void
    {
        $this->transactionFee = $transactionFee;
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
