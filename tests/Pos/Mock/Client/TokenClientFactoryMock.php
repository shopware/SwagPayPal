<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Client\TokenClient;
use Swag\PayPal\Pos\Client\TokenClientFactory;

/**
 * @internal
 */
#[Package('checkout')]
class TokenClientFactoryMock extends TokenClientFactory
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
        parent::__construct($this->logger);
    }

    public function createTokenClient(): TokenClient
    {
        return new TokenClientMock($this->logger);
    }
}
