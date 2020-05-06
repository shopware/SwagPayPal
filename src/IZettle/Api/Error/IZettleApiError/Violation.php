<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Error\IZettleApiError;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class Violation extends IZettleStruct
{
    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var string
     */
    private $developerMessage;

    /**
     * @var string
     */
    private $constraintType;

    public function toString(): string
    {
        return sprintf('The property "%s" %s', $this->propertyName, $this->developerMessage);
    }

    protected function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    protected function setDeveloperMessage(string $developerMessage): void
    {
        $this->developerMessage = $developerMessage;
    }

    protected function setConstraintType(string $constraintType): void
    {
        $this->constraintType = $constraintType;
    }
}
