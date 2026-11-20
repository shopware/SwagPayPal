<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Run\LogHandler;
use Swag\PayPal\Util\Log\LogKey;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LogHandler::class)]
class LogHandlerTest extends TestCase
{
    public function testStoresTheExceptionMessageWithTheLine(): void
    {
        $handler = new LogHandler();
        $logger = new Logger('test', [$handler], [new PsrLogMessageProcessor()]);

        $logger->error('Could not update the product at PayPal POS', [LogKey::EXCEPTION => new \RuntimeException('The product is locked')]);

        static::assertSame(
            [['level' => Level::Error->value, 'message' => 'Could not update the product at PayPal POS: The product is locked']],
            $handler->getLogs(),
        );
    }

    public function testStoresTheProductOfTheLine(): void
    {
        $product = new ProductEntity();
        $product->setId('variant-id');
        $product->setParentId('parent-id');
        $product->setVersionId('version-id');

        $handler = new LogHandler();
        $logger = new Logger('test', [$handler], [new PsrLogMessageProcessor()]);

        $logger->info('Changed the stock of {productName} by {change}', ['product' => $product, 'productName' => 'Shirt', 'change' => 3]);

        static::assertSame(
            [[
                'level' => Level::Info->value,
                'message' => 'Changed the stock of Shirt by 3',
                'productId' => 'parent-id',
                'productVersionId' => 'version-id',
            ]],
            $handler->getLogs(),
        );
    }
}
