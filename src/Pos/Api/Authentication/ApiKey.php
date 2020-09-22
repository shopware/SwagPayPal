<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Authentication;

use Swag\PayPal\Pos\Api\Authentication\ApiKey\Header;
use Swag\PayPal\Pos\Api\Authentication\ApiKey\Payload;
use Swag\PayPal\Pos\Api\Common\PosStruct;

class ApiKey extends PosStruct
{
    /**
     * @var Header
     */
    private $header;

    /**
     * @var Payload
     */
    private $payload;

    /**
     * @var string
     */
    private $signature;

    public function getHeader(): Header
    {
        return $this->header;
    }

    public function setHeader(Header $header): void
    {
        $this->header = $header;
    }

    public function getPayload(): Payload
    {
        return $this->payload;
    }

    public function setPayload(Payload $payload): void
    {
        $this->payload = $payload;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }
}
