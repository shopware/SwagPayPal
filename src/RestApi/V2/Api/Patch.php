<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_patch')]
#[Package('checkout')]
class Patch extends PayPalApiStruct
{
    public const OPERATION_ADD = 'add';
    public const OPERATION_REPLACE = 'replace';
    public const OPERATION_REMOVE = 'remove';

    #[OA\Property(type: 'string')]
    protected string $op;

    #[OA\Property(type: 'string')]
    protected string $path;

    #[OA\Property(nullable: true, oneOf: [
        new OA\Schema(type: 'integer'),
        new OA\Schema(type: 'float'),
        new OA\Schema(type: 'string'),
        new OA\Schema(type: 'boolean'),
        new OA\Schema(type: 'array', items: new OA\Items(type: 'mixed')),
    ])]
    protected int|float|string|bool|array|null $value;

    #[OA\Property(type: 'string')]
    protected string $from;

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

    public function getValue(): array|bool|float|int|string|null
    {
        return $this->value;
    }

    public function setValue(array|bool|float|int|string|null $value): void
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
