<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\DisputeOutcome\AmountRefunded;

class DisputeOutcome extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $outcomeCode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var AmountRefunded
     */
    protected $amountRefunded;

    public function getOutcomeCode(): string
    {
        return $this->outcomeCode;
    }

    public function setOutcomeCode(string $outcomeCode): void
    {
        $this->outcomeCode = $outcomeCode;
    }

    public function getAmountRefunded(): AmountRefunded
    {
        return $this->amountRefunded;
    }

    public function setAmountRefunded(AmountRefunded $amountRefunded): void
    {
        $this->amountRefunded = $amountRefunded;
    }
}
