<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class Patch extends PayPalStruct
{
    public const OPERATION_ADD = 'add';
    public const OPERATION_REPLACE = 'replace';

    /**
     * @var string
     */
    protected $op;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var mixed
     */
    protected $value;

    public function setValue($value): void
    {
        $this->value = $value;
    }

    protected function setOp(string $op): void
    {
        $this->op = $op;
    }

    protected function setPath(string $path): void
    {
        $this->path = $path;
    }
}
