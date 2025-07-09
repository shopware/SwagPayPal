<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Exception;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\AgenticCommerceV1\AgentErrorDetailCollection;

#[Package('checkout')]
abstract class AgentHttpException extends HttpException
{
    private AgentErrorDetailCollection $details;

    public function __construct(
        int $statusCode,
        string $errorCode,
        string $message,
        array $parameters = [],
        AgentErrorDetailCollection $details = new AgentErrorDetailCollection(),
    ) {
        $this->details = $details;

        parent::__construct($statusCode, $errorCode, $message, $parameters);
    }

    public function getDetails(): AgentErrorDetailCollection
    {
        return $this->details;
    }
}
