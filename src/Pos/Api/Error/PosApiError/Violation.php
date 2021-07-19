<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Error\PosApiError;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class Violation extends PosStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $propertyName;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $developerMessage;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $constraintType;

    public function toString(): string
    {
        return \sprintf('The property "%s" %s', $this->propertyName, $this->developerMessage);
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function getDeveloperMessage(): string
    {
        return $this->developerMessage;
    }

    public function setDeveloperMessage(string $developerMessage): void
    {
        $this->developerMessage = $developerMessage;
    }

    public function getConstraintType(): string
    {
        return $this->constraintType;
    }

    public function setConstraintType(string $constraintType): void
    {
        $this->constraintType = $constraintType;
    }
}
