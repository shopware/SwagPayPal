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

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_twig_result',
    required: ['storefront', 'documents'],
    properties: [
        new OA\Property(property: 'storefront', ref: TemplateAreaResult::class),
        new OA\Property(property: 'documents', ref: TemplateAreaResult::class),
    ],
)]
#[Package('checkout')]
final class TwigDiagnosticsResult extends Struct
{
    public function __construct(
        public readonly TemplateAreaResult $storefront,
        public readonly TemplateAreaResult $documents,
    ) {
    }
}
