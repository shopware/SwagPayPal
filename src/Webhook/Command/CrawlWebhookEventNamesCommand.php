<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Command;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'swag:paypal:crawl:webhooks',
    description: 'Crawls the PayPal developer website for webhook event names and updates "Swag\PayPal\Webhook\WebhookEventTypes"'
)]
#[Package('checkout')]
class CrawlWebhookEventNamesCommand extends Command
{
    private const PAYPAL_WEBHOOK_PAGE = 'https://developer.paypal.com/docs/api-basics/notifications/webhooks/event-names/';
    private const WEBHOOK_NAME_KEY = 'webhookName';
    private const WEBHOOK_DESCRIPTION_KEY = 'webhookDescription';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $html = \file_get_contents(self::PAYPAL_WEBHOOK_PAGE);
        if ($html === false) {
            throw new \RuntimeException(\sprintf(
                'Could not get PayPal webhook website (%s). Please check, if this page is still correct',
                self::PAYPAL_WEBHOOK_PAGE
            ));
        }

        $webhookTables = [];

        $webhookTables[] = (new Crawler($html))->filterXPath('//table')->each(static function (Crawler $table) {
            return $table->filterXPath('//tbody//tr')->each(static function (Crawler $webhookTableRow) {
                $webhookTableColumns = $webhookTableRow->children();
                $webhookNameNode = $webhookTableColumns->getNode(0);
                $webhookDescriptionNode = $webhookTableColumns->getNode(1);

                if ($webhookNameNode === null || $webhookDescriptionNode === null) {
                    return [];
                }

                return [
                    self::WEBHOOK_NAME_KEY => $webhookNameNode->textContent,
                    self::WEBHOOK_DESCRIPTION_KEY => $webhookDescriptionNode->textContent,
                ];
            });
        });

        $webhooksString = '';
        foreach ($webhookTables as $webhookTable) {
            foreach ($webhookTable as $webhooks) {
                $webhooksString .= "\n";
                foreach ($webhooks as $webhook) {
                    $webhookName = $webhook[self::WEBHOOK_NAME_KEY];
                    if (\mb_strpos($webhooksString, $webhookName) !== false) {
                        continue;
                    }

                    $webhookDescription = $webhook[self::WEBHOOK_DESCRIPTION_KEY];
                    $webhookConstName = \str_replace(['.', '-'], '_', $webhookName);
                    $webhooksString .= \sprintf(
                        "    /* %s */\n    public const %s = '%s';\n",
                        $webhookDescription,
                        $webhookConstName,
                        $webhookName
                    );
                }
            }
        }

        $webhookEventTypesClass = \sprintf($this->getClassTemplate(), self::PAYPAL_WEBHOOK_PAGE, $webhooksString);

        $webhookEventTypesClassPath = __DIR__ . '/../WebhookEventTypes.php';
        $result = \file_put_contents($webhookEventTypesClassPath, $webhookEventTypesClass, \LOCK_EX);
        if ($result === false) {
            throw new \RuntimeException(\sprintf('File "%s" could not be written', $webhookEventTypesClassPath));
        }

        return 0;
    }

    private function getClassTemplate(): string
    {
        return <<<EOD
<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

/**
 * @url %s
 */
final class WebhookEventTypes
{
    public const ALL_EVENTS = '*';
%s
}

EOD;
    }
}
