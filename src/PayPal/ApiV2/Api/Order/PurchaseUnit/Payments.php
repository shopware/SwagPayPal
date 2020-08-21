<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit;

use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\PayPal\PayPalApiStruct;

class Payments extends PayPalApiStruct
{
    /**
     * @var Authorization[]|null
     */
    protected $authorizations;

    /**
     * @var Capture[]|null
     */
    protected $captures;

    /**
     * @var Refund[]|null
     */
    protected $refunds;

    /**
     * @return Authorization[]|null
     */
    public function getAuthorizations(): ?array
    {
        return $this->authorizations;
    }

    /**
     * @param Authorization[]|null $authorizations
     */
    public function setAuthorizations(?array $authorizations): void
    {
        $this->authorizations = $authorizations;
    }

    /**
     * @return Capture[]|null
     */
    public function getCaptures(): ?array
    {
        return $this->captures;
    }

    /**
     * @param Capture[]|null $captures
     */
    public function setCaptures(?array $captures): void
    {
        $this->captures = $captures;
    }

    /**
     * @return Refund[]|null
     */
    public function getRefunds(): ?array
    {
        return $this->refunds;
    }

    /**
     * @param Refund[]|null $refunds
     */
    public function setRefunds(?array $refunds): void
    {
        $this->refunds = $refunds;
    }
}
