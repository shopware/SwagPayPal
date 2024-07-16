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
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'swag:paypal:openapi:generate',
    description: 'Generate OpenAPI schema for PayPal API.',
)]
#[Package('checkout')]
class GenerateOpenApi extends Command
{
    private const ROOT_DIR = __DIR__ . '/../../..';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);

        $generator = new Generator($logger);
        $pipeline = $generator->getProcessorPipeline()
            ->add(new PayPalApiStructSnakeCasePropertiesProcessor())
            ->add(new RequireNonOptionalPropertiesProcessor());

        $openApi = $generator->setProcessorPipeline($pipeline)->generate([
            Util::finder(
                self::ROOT_DIR . '/src',
                [self::ROOT_DIR . '/src/DevOps', self::ROOT_DIR . '/src/Resources'] // ignored directories
            ),
        ]);

        if ($openApi === null) {
            // @phpstan-ignore-next-line
            throw new \RuntimeException('Failed to generate OpenAPI schema');
        }

        $cacheDir = self::ROOT_DIR . '/var/cache';

        if (!\is_dir($cacheDir) && !\mkdir($cacheDir, 0777, true)) {
            echo 'Failed to create var/cache directory';

            return 1;
        }

        \file_put_contents($cacheDir . '/openapi.yaml', $openApi->toYaml());

        return 0;
    }
}
