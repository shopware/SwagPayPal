<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Exception;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\AgentErrorDetailCollection;

#[Package('checkout')]
abstract class AgentHttpException extends HttpException
{
    public function __construct(
        int $statusCode,
        string $errorCode,
        string $message,
        array $parameters = [],
        protected AgentErrorDetailCollection $details = new AgentErrorDetailCollection(),
        ?\Throwable $previous = null
    ) {
        parent::__construct($statusCode, $errorCode, $message, $parameters, $previous);
    }

    public function getDetails(): AgentErrorDetailCollection
    {
        return $this->details;
    }
}
