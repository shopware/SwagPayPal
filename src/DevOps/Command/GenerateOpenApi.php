<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\Command;

use OpenApi\Generator;
use OpenApi\Util;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DevOps\OpenApi\PayPalApiStructSnakeCasePropertiesProcessor;
use Swag\PayPal\DevOps\OpenApi\RequireNonOptionalPropertiesProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(
    name: 'swag:paypal:openapi:generate',
    description: 'Generate OpenAPI schema for PayPal API.',
)]
#[Package('checkout')]
class GenerateOpenApi extends Command
{
    private const ROOT_DIR = __DIR__ . '/../../..';
    private const STORE_API_DIR = self::ROOT_DIR . '/src/Resources/Schema/StoreApi';
    private const ADMIN_API_DIR = self::ROOT_DIR . '/src/Resources/Schema/AdminApi';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);

        $generator = new Generator($logger);
        $pipeline = $generator->getProcessorPipeline()
            ->add(new PayPalApiStructSnakeCasePropertiesProcessor())
            ->add(new RequireNonOptionalPropertiesProcessor());

        $generator = $generator->setProcessorPipeline($pipeline);

        $storeApi = $input->getOption('store-api');
        $adminApi = $input->getOption('admin-api');
        $style = new SymfonyStyle($input, $output);

        try {
            if ($storeApi) {
                $this->generateStoreApiSchema($style, $generator);
            }

            if ($adminApi) {
                $this->generateAdminApiSchema($style, $generator);
            }
        } catch (\RuntimeException $e) {
            $style->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function generateStoreApiSchema(SymfonyStyle $style, Generator $generator): void
    {
        $file = \realpath(self::STORE_API_DIR . '/openapi.json');
        \assert($file !== false);

        if (!\is_dir(self::STORE_API_DIR)) {
            throw new \RuntimeException('Failed to find Store API directory at: ' . self::STORE_API_DIR);
        }

        $openApi = $generator->generate([
            Util::finder(self::ROOT_DIR . '/src/RestApi'),
            Util::finder(self::ROOT_DIR . '/src/Checkout'),
            Util::finder(self::ROOT_DIR . '/../../../vendor/shopware/paypal-sdk/src/Struct'),
        ])?->toJson();

        if ($openApi === null) {
            throw new \RuntimeException('Failed to generate OpenAPI schema for Store API');
        }

        if (\file_put_contents($file, $openApi) === false) {
            throw new \RuntimeException('Failed to write Store API schema to: ' . $file);
        }

        $style->success('Written Store API schema to: ' . $file);
    }

    protected function generateAdminApiSchema(SymfonyStyle $style, Generator $generator): void
    {
        $file = \realpath(self::ADMIN_API_DIR . '/openapi.json');
        \assert($file !== false);

        if (!\is_dir(self::ADMIN_API_DIR)) {
            throw new \RuntimeException('Failed to find Admin API directory at: ' . self::ADMIN_API_DIR);
        }

        $openApi = $generator->generate([
            Util::finder(self::ROOT_DIR . '/src/RestApi'),
            Util::finder(self::ROOT_DIR . '/src/Administration'),
            Util::finder(self::ROOT_DIR . '/src/Dispute'),
            Util::finder(self::ROOT_DIR . '/src/OrdersApi'),
            Util::finder(self::ROOT_DIR . '/src/PaymentsApi'),
            Util::finder(self::ROOT_DIR . '/src/Pos'),
            Util::finder(self::ROOT_DIR . '/src/Setting'),
            Util::finder(self::ROOT_DIR . '/src/Webhook'),
            Util::finder(self::ROOT_DIR . '/../../../vendor/shopware/paypal-sdk/src/Struct'),
            Util::finder(__DIR__ . '/Polyfill'),
        ])?->toJson();

        if ($openApi === null) {
            throw new \RuntimeException('Failed to generate OpenAPI schema for Admin API');
        }

        if (\file_put_contents($file, $openApi) === false) {
            throw new \RuntimeException('Failed to write Admin API schema to: ' . $file);
        }

        $style->success('Written Admin API schema to: ' . $file);
    }

    protected function configure(): void
    {
        $this->addOption('store-api', null, InputOption::VALUE_NEGATABLE, 'Generate store-api schema into src/Resources/Schema/StoreApi', true);
        $this->addOption('admin-api', null, InputOption::VALUE_NEGATABLE, 'Generate store-api schema into src/Resources/Schema/AdminApi', true);
    }
}
