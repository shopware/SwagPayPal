<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\SalesChannel;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\CheckoutRoute;
use Swag\PayPal\AgentCommerce\SalesChannel\CreateCartRoute;
use Swag\PayPal\AgentCommerce\SalesChannel\GetCartRoute;
use Swag\PayPal\AgentCommerce\SalesChannel\UpdateCartRoute;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class DefaultRouteScopeTest extends TestCase
{
    /**
     * @var array<class-string, list<string>>
     */
    private static array $expectedDefaults = [
        CreateCartRoute::class => [AgentSource::SCOPE_CART],
        GetCartRoute::class => [AgentSource::SCOPE_CART],
        UpdateCartRoute::class => [AgentSource::SCOPE_CART],
        CheckoutRoute::class => [AgentSource::SCOPE_CHECKOUT],
    ];

    public function testRoutesHaveCorrectScopeDefaults(): void
    {
        foreach (self::$expectedDefaults as $class => $expectedDefaults) {
            $reflectionClass = new \ReflectionClass($class);
            $attributes = $reflectionClass->getAttributes(Route::class);

            static::assertNotEmpty($attributes, \sprintf('No Route attribute found for class %s', $class));

            /** @var Route $routeAttribute */
            $routeAttribute = $attributes[0]->newInstance();
            static::assertArrayHasKey('_agentScope', $routeAttribute->getDefaults());

            static::assertEquals($expectedDefaults, $routeAttribute->getDefaults()['_agentScope'], \sprintf('Incorrect _agentScope defaults for class %s', $class));
        }
    }
}
