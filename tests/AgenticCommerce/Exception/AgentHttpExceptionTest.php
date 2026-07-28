<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Exception\AgentHttpException;
use Swag\PayPal\AgenticCommerce\Struct\V1\AgentErrorDetail;
use Swag\PayPal\AgenticCommerce\Struct\V1\AgentErrorDetailCollection;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentHttpException::class)]
class AgentHttpExceptionTest extends TestCase
{
    public function testPublicAPI(): void
    {
        $detail1 = new AgentErrorDetail();
        $detail1->assign([
            'field' => 'foo',
            'issue' => 'bar',
            'description' => 'baz',
        ]);

        $details = new AgentErrorDetailCollection([$detail1]);

        $e = new class(500, 'TEST_EXCEPTION', 'Test exception message: {{ foo }}', ['foo' => 'bar'], $details) extends AgentHttpException {};

        static::assertSame(500, $e->getStatusCode());
        static::assertSame('TEST_EXCEPTION', $e->getErrorCode());
        static::assertSame('Test exception message: bar', $e->getMessage());
        static::assertSame(['foo' => 'bar'], $e->getParameters());
        static::assertSame($details, $e->getDetails());
    }
}
