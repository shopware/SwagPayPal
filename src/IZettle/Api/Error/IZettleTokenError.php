<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Error;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class IZettleTokenError extends IZettleStruct
{
    /**
     * @var string
     */
    private $error;

    /**
     * @var string
     */
    private $errorDescription;

    public function toString(): string
    {
        $message = $this->error;

        if ($this->errorDescription) {
            $message .= ":\n";
            $message .= $this->errorDescription;
        }

        return $message;
    }

    public function getError(): string
    {
        return $this->error;
    }

    protected function setError(string $error): void
    {
        $this->error = $error;
    }

    protected function setErrorDescription(string $errorDescription): void
    {
        $this->errorDescription = $errorDescription;
    }
}
