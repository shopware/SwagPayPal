<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\Command\Polyfill;

use OpenApi\Attributes as OA;
use OpenApi\Attributes\Items;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
#[OA\Schema(schema: 'error')]
class Error
{
    #[OA\Property(type: 'string')]
    protected string $code;

    #[OA\Property(type: 'string')]
    protected string $status;

    #[OA\Property(type: 'string')]
    protected string $title;

    #[OA\Property(type: 'string')]
    protected string $detail;

    #[OA\Property(type: 'array', items: new Items(
        type: 'object',
        properties: [new OA\Property(
            property: 'parameters',
            type: 'array',
            items: new Items(type: 'mixed')
        )]
    ))]
    protected array $meta;
}
