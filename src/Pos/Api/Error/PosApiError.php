<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Error;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Error\PosApiError\Violation;

#[Package('checkout')]
class PosApiError extends PosStruct
{
    public const ERROR_TYPE_ITEM_ALREADY_EXISTS = 'ITEM_ALREADY_EXIST';
    public const ERROR_TYPE_ENTITY_NOT_FOUND = 'ENTITY_NOT_FOUND';
    public const ERROR_TYPE_PRODUCT_NOT_TRACKED = 'PRODUCT_NOT_TRACKED';
    public const ERROR_TYPE_SUBSCRIPTION_EXISTS = 'SUBSCRIPTION_ALREADY_EXIST';
    public const ERROR_TYPE_SUBSCRIPTION_NOT_EXISTS = 'SUBSCRIPTION_NOT_FOUND';

    protected string $developerMessage;

    protected ?string $errorType = null;

    /**
     * @var Violation[]|null
     */
    protected ?array $violations = null;

    public function getViolationsAsString(): string
    {
        $message = '';
        if ($this->violations === [] || $this->violations === null) {
            return $message;
        }

        foreach ($this->violations as $violation) {
            $message .= $violation->toString();
        }

        return $message;
    }

    public function getDeveloperMessage(): string
    {
        return $this->developerMessage;
    }

    public function setDeveloperMessage(string $developerMessage): void
    {
        $this->developerMessage = $developerMessage;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function setErrorType(?string $errorType): void
    {
        $this->errorType = $errorType;
    }

    public function getViolations(): ?array
    {
        return $this->violations;
    }

    public function setViolations(?array $violations): void
    {
        $this->violations = $violations;
    }
}
