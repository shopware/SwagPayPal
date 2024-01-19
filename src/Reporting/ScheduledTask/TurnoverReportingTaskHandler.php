<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Reporting\ScheduledTask;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: TurnoverReportingTask::class)]
class TurnoverReportingTaskHandler extends ScheduledTaskHandler
{
    private const API_IDENTIFIER = '9b6f559b-5ca1-4969-b23d-e0aa2c01d562';

    private Client $client;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly EntityRepository $transactionReportRepository,
        private readonly string $shopwareVersion,
        private readonly ?string $instanceId,
    ) {
        $this->client = new Client(['base_uri' => 'https://api.shopware.com']);
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $transactionReportIds = $this->transactionReportRepository
            ->searchIds(new Criteria(), Context::createDefaultContext())->getIds();

        /**
         * All transactions no longer in paid state will be ignored, but deleted at the end
         *
         * e.g. ['EUR' => '900.98', 'GBP' => '100']
         *
         * @var array<string, string> $reports
         */
        $reports = $this->connection->executeQuery(
            '
                SELECT tr.currency_iso, SUM(tr.total_price) as turnover
                    FROM swag_paypal_transaction_report as tr
                LEFT JOIN order_transaction as ot
                    ON tr.order_transaction_id = ot.id AND tr.order_transaction_version_id = ot.version_id
                LEFT JOIN state_machine_state as sms
                    ON ot.state_id = sms.id
                WHERE sms.technical_name = (:state) AND LOWER(HEX(tr.order_transaction_id)) in (:ids)
                GROUP BY tr.currency_iso
            ',
            ['state' => OrderTransactionStates::STATE_PAID, 'ids' => $transactionReportIds],
            ['ids' => ArrayParameterType::STRING]
        )->fetchAllKeyValue();

        $requests = [];
        foreach ($reports as $currency => $turnover) {
            $body = [
                'identifier' => self::API_IDENTIFIER,
                'reportDate' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                'instanceId' => $this->instanceId,
                'shopwareVersion' => $this->shopwareVersion,
                'reportDataKeys' => ['turnover' => \round((float) $turnover, 2)],
                'currency' => $currency,
            ];

            $requests[$currency] = $this->client->postAsync(
                '/shopwarepartners/reports/technology',
                [RequestOptions::JSON => $body]
            );
        }

        $rejectedCurrencies = [];
        /** @var array{state: string, reason: ClientException} $response */
        foreach (Utils::settle($requests)->wait() as $currency => $response) {
            if ($response['state'] !== Promise::REJECTED) {
                continue;
            }

            $this->logger->warning(\sprintf(
                'Failed to report turnover for "%s": %s',
                $currency,
                $response['reason']->getMessage()
            ));

            $rejectedCurrencies[] = $currency;
        }

        $this->connection->executeStatement(
            '
            DELETE FROM `swag_paypal_transaction_report`
            WHERE LOWER(HEX(`order_transaction_id`)) IN (:ids)
            ' . ($rejectedCurrencies ? 'AND `currency_iso` NOT IN (:rejectedCurrencies)' : ''),
            ['ids' => $transactionReportIds, 'rejectedCurrencies' => $rejectedCurrencies],
            ['ids' => ArrayParameterType::STRING, 'rejectedCurrencies' => ArrayParameterType::STRING],
        );
    }
}
