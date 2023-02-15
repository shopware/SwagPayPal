<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Webhook\WebhookServiceTest;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
class RouterMock implements RouterInterface
{
    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if ($parameters['sw-token'] === WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN) {
            return GuzzleClientMock::GET_WEBHOOK_URL;
        }

        $parameterString = '?';
        foreach ($parameters as $key => $parameter) {
            $parameterString .= $key . '=' . $parameter;
        }

        return $name . $parameterString;
    }

    public function match($pathinfo): array
    {
        return [];
    }
}
