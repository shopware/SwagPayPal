<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payee\DisplayData;

class Payee extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $emailAddress;

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var DisplayData
     */
    protected $displayData;

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function getDisplayData(): DisplayData
    {
        return $this->displayData;
    }

    public function setDisplayData(DisplayData $displayData): void
    {
        $this->displayData = $displayData;
    }
}
