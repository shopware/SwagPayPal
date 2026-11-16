<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverride;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_twig_area_result',
    required: ['applicable', 'overrides'],
    properties: [
        new OA\Property(property: 'applicable', description: 'False when nothing renders the area\'s templates, for example the storefront of a headless Sales Channel', type: 'boolean'),
        new OA\Property(property: 'overrides', type: 'array', items: new OA\Items(ref: TemplateOverride::class)),
    ],
)]
#[Package('checkout')]
final class TemplateAreaResult extends Struct
{
    /**
     * @param list<TemplateOverride> $overrides
     */
    public function __construct(
        public readonly bool $applicable,
        public readonly array $overrides,
    ) {
    }
}
