<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\Client;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\AbstractClient;

/**
 * @internal
 */
#[Package('checkout')]
class TestClient extends AbstractClient
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function send(string $method, string $uri, array $options = []): array
    {
        return match ($method) {
            'POST' => $this->post($uri, $options),
            'DELETE' => $this->delete($uri, $options),
            default => $this->get($uri, $options),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function sendForHeaders(string $uri): array
    {
        return $this->getHeaders($uri);
    }
}
