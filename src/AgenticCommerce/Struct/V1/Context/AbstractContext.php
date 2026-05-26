<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1\Context;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
abstract class AbstractContext extends PayPalApiStruct
{
    /**
     * Specific business rule issue type
     */
    #[OA\Property(type: 'string')]
    protected string $specificIssue;

    public function getSpecificIssue(): string
    {
        return $this->specificIssue;
    }

    public function setSpecificIssue(string $specificIssue): void
    {
        if (!\in_array($specificIssue, static::getSpecificIssues(), true)) {
            throw new \InvalidArgumentException(\sprintf('Specific issue "%s" is not valid.', $specificIssue));
        }

        $this->specificIssue = $specificIssue;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }

    /**
     * @return string[]
     */
    abstract protected static function getSpecificIssues(): array;
}
