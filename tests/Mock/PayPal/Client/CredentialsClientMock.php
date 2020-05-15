<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\PayPal\Client\CredentialsClient;

class CredentialsClientMock extends CredentialsClient
{
    public function __construct(string $url, LoggerInterface $logger)
    {
        parent::__construct($url, $logger);
        $this->client = new GuzzleClientMock(['base_uri' => $url]);
    }
}
