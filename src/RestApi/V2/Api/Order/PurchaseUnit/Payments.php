<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;

class Payments extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Authorization[]|null
     */
    protected $authorizations;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Capture[]|null
     */
    protected $captures;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
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
