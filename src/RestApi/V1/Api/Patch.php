<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_patch')]
#[Package('checkout')]
class Patch extends PayPalApiStruct
{
    public const OPERATION_ADD = 'add';
    public const OPERATION_REPLACE = 'replace';

    #[OA\Property(type: 'string', enum: [self::OPERATION_ADD, self::OPERATION_REPLACE])]
    protected string $op;

    #[OA\Property(type: 'string')]
    protected string $path;

    #[OA\Property(oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'array', items: new OA\Items(type: 'mixed'))])]
    protected array|string $value;

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

    public function getValue(): array|string
    {
        return $this->value;
    }

    public function setValue(array|string $value): void
    {
        $this->value = $value;
    }
}
