<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\OpenApi;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class RequireNonOptionalPropertiesProcessor
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var OA\Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(OA\Schema::class, true);

        foreach ($schemas as $schema) {
            if (!$schema->_context?->is('class') || !Generator::isDefault($schema->required)) {
                continue;
            }

            $this->requireProps($schema);

            if (!Generator::isDefault($schema->allOf)) {
                foreach ($schema->allOf as $item) {
                    $this->requireProps($item);
                }
            }

            if (!Generator::isDefault($schema->anyOf)) {
                foreach ($schema->anyOf as $item) {
                    $this->requireProps($item);
                }
            }
        }
    }

    private function requireProps(OA\Schema $schema): void
    {
        if (Generator::isDefault($schema->properties)) {
            return;
        }

        $required = Generator::isDefault($schema->required) ? [] : $schema->required;
        foreach ($schema->properties as $property) {
            if (Generator::isDefault($property->property) || !Generator::isDefault($property->default)) {
                continue;
            }

            $required[] = $property->property;
        }

        $schema->required = empty($required) ? $schema->required : $required;
    }
}
