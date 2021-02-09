<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CanceledRecurringBilling\CancellationDetails;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CanceledRecurringBilling\ExpectedRefund;

class CanceledRecurringBilling extends PayPalApiStruct
{
    /**
     * @var ExpectedRefund
     */
    protected $expectedRefund;

    /**
     * @var CancellationDetails
     */
    protected $cancellationDetails;

    public function getExpectedRefund(): ExpectedRefund
    {
        return $this->expectedRefund;
    }

    public function setExpectedRefund(ExpectedRefund $expectedRefund): void
    {
        $this->expectedRefund = $expectedRefund;
    }

    public function getCancellationDetails(): CancellationDetails
    {
        return $this->cancellationDetails;
    }

    public function setCancellationDetails(CancellationDetails $cancellationDetails): void
    {
        $this->cancellationDetails = $cancellationDetails;
    }
}
