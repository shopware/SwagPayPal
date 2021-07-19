<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use Swag\PayPal\RestApi\PayPalApiStruct;

class Patch extends PayPalApiStruct
{
    public const OPERATION_ADD = 'add';
    public const OPERATION_REPLACE = 'replace';
    public const OPERATION_REMOVE = 'remove';

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $op;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $path;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var int|float|string|bool|array|null
     */
    protected $value;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $from;

    public function getOp(): string
    {
        return $this->op;
    }

    public function setOp(string $op): void
    {
        $this->op = $op;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return array|bool|float|int|string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array|bool|float|int|string|null $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }
}
