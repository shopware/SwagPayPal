<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class HoneyClientMock implements ClientInterface
{
    public function __construct(
        private readonly array $config
    ) {
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return new Response();
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return new Promise();
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return new Promise();
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return new Response();
    }

    public function getConfig(?string $option = null)
    {
        if ($option !== null) {
            if (isset($this->config[$option])) {
                return $this->config[$option];
            }

            return null;
        }

        return $this->config;
    }
}
