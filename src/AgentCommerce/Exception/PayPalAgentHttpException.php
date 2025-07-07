<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Exception;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
abstract class PayPalAgentHttpException extends HttpException
{
    /**
     * @var PayPalAgentErrorResponseDetail[]
     */
    private array $details;

    /**
     * @param PayPalAgentErrorResponseDetail[] $details
     */
    public function __construct(
        int $statusCode,
        string $errorCode,
        string $message,
        array $parameters = [],
        array $details = [],
    ) {
        $this->details = $details;

        parent::__construct($statusCode, $errorCode, $message, $parameters);
    }

    /**
     * @return PayPalAgentErrorResponseDetail[]
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
