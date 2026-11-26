<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Log\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Diagnostics\Log\DataAbstractionLayer\LogLineCollection;
use Swag\PayPal\Diagnostics\Log\DataAbstractionLayer\LogLineDefinition;
use Swag\PayPal\Diagnostics\Log\DataAbstractionLayer\LogLineEntity;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LogLineDefinition::class)]
#[CoversClass(LogLineEntity::class)]
#[CoversClass(LogLineCollection::class)]
class LogLineDefinitionTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testALineOfThePayPalLoggerCanBeReadBack(): void
    {
        $salesChannelId = Uuid::randomHex();
        $logger = static::getContainer()->get('monolog.logger.paypal');
        static::assertInstanceOf(LoggerInterface::class, $logger);

        $logger->error('Could not capture the payment', Log::context(code: LogCode::CHECKOUT_PAYMENT_FAILED, salesChannelId: $salesChannelId));

        $criteria = (new Criteria())->addFilter(new EqualsFilter('code', LogCode::CHECKOUT_PAYMENT_FAILED));
        $lines = $this->getRepository()->search($criteria, Context::createDefaultContext())->getEntities();

        static::assertInstanceOf(LogLineCollection::class, $lines);
        static::assertCount(1, $lines);
        $line = $lines->first();
        static::assertInstanceOf(LogLineEntity::class, $line);
        static::assertSame(400, $line->getLevel());
        static::assertSame('Could not capture the payment', $line->getMessage());
        static::assertSame(LogCode::CHECKOUT_PAYMENT_FAILED, $line->getCode());
        static::assertSame($salesChannelId, $line->getSalesChannelId());
        static::assertSame($salesChannelId, $line->getContext()[Log::SALES_CHANNEL_ID] ?? null);
        static::assertSame($line->getExtra()['uid'] ?? null, $line->getUid());
        static::assertNotNull($line->getCreatedAt());
    }

    public function testContextAndExtraCanBeSearched(): void
    {
        $this->insertLine('First line', extra: ['needle' => 'needle-extra-uid']);
        $this->insertLine('Second line', context: ['orderId' => 'needle-context-order']);
        $this->insertLine('Third line', context: ['orderId' => 'other']);

        static::assertSame(['First line'], $this->searchMessages(new ContainsFilter('extra', 'needle-extra-uid')));
        static::assertSame(['Second line'], $this->searchMessages(new ContainsFilter('context', 'needle-context-order')));
    }

    public function testLinesCanBeFilteredBySalesChannel(): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->insertLine('In the Sales Channel', context: [Log::SALES_CHANNEL_ID => $salesChannelId]);
        $this->insertLine('In another Sales Channel', context: [Log::SALES_CHANNEL_ID => Uuid::randomHex()]);
        $this->insertLine('Without Sales Channel');

        static::assertSame(
            ['In the Sales Channel'],
            $this->searchMessages(new EqualsFilter('salesChannelId', $salesChannelId)),
        );
    }

    public function testTheCodesCanBeAggregated(): void
    {
        $this->insertLine('A', code: 'LOG_TEST_CODE_A');
        $this->insertLine('B', code: 'LOG_TEST_CODE_A');
        $this->insertLine('C', code: 'LOG_TEST_CODE_B');
        $this->insertLine('D');

        $criteria = (new Criteria())->setLimit(1)->addAggregation(new TermsAggregation('codes', 'code'));
        $aggregation = $this->getRepository()->search($criteria, Context::createDefaultContext())->getAggregations()->get('codes');

        static::assertInstanceOf(TermsResult::class, $aggregation);
        $keys = \array_filter(\array_map(static fn ($bucket) => $bucket->getKey(), $aggregation->getBuckets()));
        static::assertContains('LOG_TEST_CODE_A', $keys);
        static::assertContains('LOG_TEST_CODE_B', $keys);
        static::assertCount(\count($keys), \array_unique($keys));
    }

    public function testWritingInTheUserScopeIsRefused(): void
    {
        $repository = $this->getRepository();
        $context = Context::createDefaultContext();

        $this->expectException(AccessDeniedHttpException::class);

        $context->scope(Context::USER_SCOPE, static fn (Context $context) => $repository->create([[
            'id' => Uuid::randomHex(),
            'level' => 400,
            'message' => 'Written through the API',
        ]], $context));
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    private function insertLine(string $message, ?string $code = null, array $context = [], array $extra = []): void
    {
        $context = $code === null ? $context : [...$context, Log::CODE => $code];
        static::getContainer()->get(Connection::class)->insert(LogLineDefinition::ENTITY_NAME, [
            'id' => Uuid::randomBytes(),
            'level' => 400,
            'message' => $message,
            'context' => $context === [] ? null : \json_encode($context, \JSON_THROW_ON_ERROR),
            'extra' => $extra === [] ? null : \json_encode($extra, \JSON_THROW_ON_ERROR),
            'created_at' => '2026-11-20 10:00:00.000',
        ]);
    }

    /**
     * @return list<string>
     */
    private function searchMessages(ContainsFilter|EqualsFilter $filter): array
    {
        $lines = $this->getRepository()->search((new Criteria())->addFilter($filter), Context::createDefaultContext())->getEntities();
        $messages = \array_values($lines->map(static fn (LogLineEntity $line) => $line->getMessage()));
        \sort($messages);

        return $messages;
    }

    /**
     * @return EntityRepository<LogLineCollection>
     */
    private function getRepository(): EntityRepository
    {
        /** @var EntityRepository<LogLineCollection> $repository */
        $repository = static::getContainer()->get(LogLineDefinition::ENTITY_NAME . '.repository');

        return $repository;
    }
}
