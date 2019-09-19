<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouterMock implements RouterInterface
{
    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
    }

    public function getRouteCollection(): RouteCollection
    {
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        $parameterString = '?';
        foreach ($parameters as $key => $parameter) {
            $parameterString .= $key . '=' . $parameter;
        }

        return $name . $parameterString;
    }

    public function match($pathinfo): array
    {
    }
}
