<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\SalesChannel\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\Struct\V1\PayPalCart;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentCartResponse::class)]
class AgentCartResponseTest extends TestCase
{
    public function testConstruct(): void
    {
        $cart = new PayPalCart();
        $cart->setId('test-token');

        $response = new AgentCartResponse($cart);
        $responseObject = $response->getObject();

        static::assertInstanceOf(ArrayStruct::class, $responseObject);
        static::assertSame(['id' => 'test-token'], $responseObject->all());
    }
}
