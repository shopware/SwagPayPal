<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Swag\PayPal\RestApi\PayPalApiStruct;

class Adjudication extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $type;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $adjudicationTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $reason;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $disputeLifeCycleStage;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAdjudicationTime(): string
    {
        return $this->adjudicationTime;
    }

    public function setAdjudicationTime(string $adjudicationTime): void
    {
        $this->adjudicationTime = $adjudicationTime;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getDisputeLifeCycleStage(): string
    {
        return $this->disputeLifeCycleStage;
    }

    public function setDisputeLifeCycleStage(string $disputeLifeCycleStage): void
    {
        $this->disputeLifeCycleStage = $disputeLifeCycleStage;
    }
}
