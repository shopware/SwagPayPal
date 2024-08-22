<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class CredentialsClientFactory
{
    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createCredentialsClient(string $url): CredentialsClient
    {
        return new CredentialsClient($url, $this->logger);
    }
}
