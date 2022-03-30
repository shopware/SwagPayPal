<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\Link;
use Swag\PayPal\RestApi\V2\Api\Order\Payer;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;

/**
 * @OA\Schema(schema="swag_paypal_v2_order")
 */
class Order extends PayPalApiStruct
{
    public const PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';

    /**
     * @OA\Property(type="string")
     */
    protected string $createTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $updateTime;

    /**
     * @OA\Property(type="string")
     */
    protected string $id;

    /**
     * @OA\Property(type="string")
     */
    protected string $intent = PaymentIntentV2::CAPTURE;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payer")
     */
    protected Payer $payer;

    /**
     * @var PurchaseUnit[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v2_order_purchase_unit"})
     */
    protected array $purchaseUnits;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_application_context")
     */
    protected ApplicationContext $applicationContext;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source")
     */
    protected ?PaymentSource $paymentSource = null;

    /**
     * @OA\Property(type="string")
     */
    protected string $status;

    /**
     * @OA\Property(type="string")
     */
    protected string $processingInstruction;

    /**
     * @var Link[]
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v2_common_link"})
     */
    protected array $links;

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

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function getPayer(): Payer
    {
        return $this->payer;
    }

    public function setPayer(Payer $payer): void
    {
        $this->payer = $payer;
    }

    /**
     * @return PurchaseUnit[]
     */
    public function getPurchaseUnits(): array
    {
        return $this->purchaseUnits;
    }

    /**
     * @param PurchaseUnit[] $purchaseUnits
     */
    public function setPurchaseUnits(array $purchaseUnits): void
    {
        $this->purchaseUnits = $purchaseUnits;
    }

    public function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(ApplicationContext $applicationContext): void
    {
        $this->applicationContext = $applicationContext;
    }

    public function getPaymentSource(): ?PaymentSource
    {
        return $this->paymentSource;
    }

    public function setPaymentSource(?PaymentSource $paymentSource): void
    {
        $this->paymentSource = $paymentSource;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getProcessingInstruction(): string
    {
        return $this->processingInstruction;
    }

    public function setProcessingInstruction(string $processingInstruction): void
    {
        $this->processingInstruction = $processingInstruction;
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

    public function getRelLink(string $rel): ?Link
    {
        foreach ($this->links as $link) {
            if ($link->getRel() === $rel) {
                return $link;
            }
        }

        return null;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
