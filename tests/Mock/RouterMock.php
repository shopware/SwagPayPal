<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock;

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
