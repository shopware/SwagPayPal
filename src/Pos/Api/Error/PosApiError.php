<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Error;

use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Error\PosApiError\Violation;

class PosApiError extends PosStruct
{
    public const ERROR_TYPE_ITEM_ALREADY_EXISTS = 'ITEM_ALREADY_EXIST';
    public const ERROR_TYPE_ENTITY_NOT_FOUND = 'ENTITY_NOT_FOUND';
    public const ERROR_TYPE_PRODUCT_NOT_TRACKED = 'PRODUCT_NOT_TRACKED';
    public const ERROR_TYPE_SUBSCRIPTION_EXISTS = 'SUBSCRIPTION_ALREADY_EXIST';
    public const ERROR_TYPE_SUBSCRIPTION_NOT_EXISTS = 'SUBSCRIPTION_NOT_FOUND';

    /**
     * @var string
     */
    private $developerMessage;

    /**
     * @var string|null
     */
    private $errorType;

    /**
     * @var Violation[]|null
     */
    private $violations;

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

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    protected function setDeveloperMessage(string $developerMessage): void
    {
        $this->developerMessage = $developerMessage;
    }

    protected function setErrorType(?string $errorType): void
    {
        $this->errorType = $errorType;
    }

    protected function setViolations(array $violations): void
    {
        $this->violations = $violations;
    }
}
