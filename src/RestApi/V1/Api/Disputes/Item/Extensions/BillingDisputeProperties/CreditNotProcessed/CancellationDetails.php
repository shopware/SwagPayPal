<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions_cancellation_details")
 */
class CancellationDetails extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $cancellationDate;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $cancellationNumber;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     * @OA\Property(type="boolean")
     */
    protected $cancelled;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $cancellationMode;

    public function getCancellationDate(): string
    {
        return $this->cancellationDate;
    }

    public function setCancellationDate(string $cancellationDate): void
    {
        $this->cancellationDate = $cancellationDate;
    }

    public function getCancellationNumber(): string
    {
        return $this->cancellationNumber;
    }

    public function setCancellationNumber(string $cancellationNumber): void
    {
        $this->cancellationNumber = $cancellationNumber;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): void
    {
        $this->cancelled = $cancelled;
    }

    public function getCancellationMode(): string
    {
        return $this->cancellationMode;
    }

    public function setCancellationMode(string $cancellationMode): void
    {
        $this->cancellationMode = $cancellationMode;
    }
}
