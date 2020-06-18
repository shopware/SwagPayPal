<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Service\ApiKeyDecoder;

class TokenClientFactory
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ApiKeyDecoder
     */
    private $apiKeyDecoder;

    public function __construct(LoggerInterface $logger, ApiKeyDecoder $apiKeyDecoder)
    {
        $this->logger = $logger;
        $this->apiKeyDecoder = $apiKeyDecoder;
    }

    public function createTokenClient(): TokenClient
    {
        return new TokenClient($this->logger, $this->apiKeyDecoder);
    }
}
