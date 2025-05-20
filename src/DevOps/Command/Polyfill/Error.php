<?php declare(strict_types=1);

namespace Swag\PayPal\DevOps\Command\Polyfill;

use OpenApi\Attributes as OA;
use OpenApi\Attributes\Items;

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

    #[OA\Property(type: 'array', items: new OA\Items(
        type: 'object',
        properties: [new OA\Property(
            property: 'parameters',
            type: 'array',
            items: new Items(type: 'mixed')
        )]
    ))]
    protected array $meta;
}
