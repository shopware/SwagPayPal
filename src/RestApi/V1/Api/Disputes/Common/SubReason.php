<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use Swag\PayPal\RestApi\PayPalApiStruct;

abstract class SubReason extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $subReason;

    public function getSubReason(): string
    {
        return $this->subReason;
    }

    public function setSubReason(string $subReason): void
    {
        $this->subReason = $subReason;
    }
}
