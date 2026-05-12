<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Error\PosApiError;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
class Violation extends PosStruct
{
    protected ?string $propertyName = null;

    protected ?string $developerMessage = null;

    protected ?string $constraintType = null;

    public function toString(): string
    {
        return \sprintf('The property "%s" %s', $this->propertyName ?? '', $this->developerMessage ?? '');
    }

    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function getDeveloperMessage(): ?string
    {
        return $this->developerMessage;
    }

    public function setDeveloperMessage(string $developerMessage): void
    {
        $this->developerMessage = $developerMessage;
    }

    public function getConstraintType(): ?string
    {
        return $this->constraintType;
    }

    public function setConstraintType(string $constraintType): void
    {
        $this->constraintType = $constraintType;
    }
}
