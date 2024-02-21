<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Operation;
use OpenApi\Generator;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Plus\PlusPaymentFinalizeController;
use Swag\PayPal\Checkout\Plus\PlusPaymentHandleController;
use Swag\PayPal\Checkout\SalesChannel\FilteredPaymentMethodRoute;
use Swag\PayPal\Storefront\Controller\PayPalController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
#[Package('checkout')]
class OpenAPISchemaTest extends TestCase
{
    public const FILE_PATTERN = '/^.+(Controller|Route)\.php/';

    public const IGNORED_ROUTES_WITHOUT_SCHEMA = [
        // Storefront controller returning routes, annotations are on the routes
        '\\' . PayPalController::class . '::createOrder',
        '\\' . PayPalController::class . '::paymentMethodEligibility',
        '\\' . PayPalController::class . '::puiPaymentInstructions',
        '\\' . PayPalController::class . '::expressPrepareCheckout',
        '\\' . PayPalController::class . '::expressCreateOrder',
        '\\' . PayPalController::class . '::expressPrepareCart',
        '\\' . PayPalController::class . '::clearVault',

        '\\' . FilteredPaymentMethodRoute::class . '::load',
        '\\' . PlusPaymentHandleController::class . '::handlePlusPayment',
        '\\' . PlusPaymentFinalizeController::class . '::finalizeTransaction',
    ];

    public const IGNORED_LOG_MESSAGES = [
        'Required @OA\Info() not found',
        '$ref "#/components/schemas/" not found for @OA\Response() in \Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCategoryRoute->load()',
    ];

    private OpenApi $oa;

    private TestHandler $logHandler;

    protected function setUp(): void
    {
        $this->logHandler = new TestHandler();
        $logger = new Logger('test', [$this->logHandler]);

        $dir = new \RecursiveDirectoryIterator(__DIR__ . '/../src');
        $ite = new \RecursiveIteratorIterator($dir);
        $files = new \RegexIterator($ite, self::FILE_PATTERN, \RegexIterator::GET_MATCH);

        $oa = Generator::scan(\array_keys(\iterator_to_array($files)), [
            'logger' => $logger,
        ]);

        static::assertInstanceOf(OpenApi::class, $oa, 'OpenAPI schema could not be generated.');

        $this->oa = $oa;
    }

    public function testGenerationWithoutWarningsOrErrors(): void
    {
        $failures = [];

        /** @var LogRecord $record */
        foreach ($this->logHandler->getRecords() as $record) {
            if ($record->level->isLowerThan(Level::Notice) || $this->ignorableRecord($record)) {
                continue;
            }

            $failures[] = $record->level->getName() . ': ' . $record->message;
        }

        static::assertEmpty($failures, \implode(\PHP_EOL, $failures));
    }

    public function testRouteSchemas(): void
    {
        $failures = [];

        foreach ($this->oa->_analysis->annotations as $annotation) {
            if (!$annotation instanceof Operation || !$annotation->_context?->class || !$annotation->_context->namespace || !$annotation->_context->method) {
                continue;
            }

            $annotation->method = \mb_strtoupper($annotation->method);

            $fqdn = $annotation->_context->namespace . '\\' . $annotation->_context->class;

            if (!\class_exists($fqdn)) {
                static::fail('Class ' . $fqdn . ' does not exist');
            }

            $routeAttributes = (new \ReflectionClass($fqdn))
                ->getMethod($annotation->_context->method)
                ->getAttributes(Route::class);

            $fqdn .= '::' . $annotation->_context->method;

            if (\count($routeAttributes) === 0) {
                continue;
            }

            $routes = \array_map(fn ($r) => $r->getArguments(), $routeAttributes);
            $routeMethods = \array_unique(\array_merge(...\array_column($routes, 'methods')));
            $routePaths = \array_column($routes, 'path');

            if (!\in_array($annotation->method, $routeMethods, true)) {
                $failures[] = $fqdn . ' was expected to have a method of "' . \implode('" or "', $routeMethods) . '", but found "' . $annotation->method . '" in OpenAPI Schema';
            } else {
                /** @phpstan-ignore-next-line - comparing parameters with Generator::UNDEFINED is valid */
                if ($annotation->method === 'GET' && $annotation->parameters === Generator::UNDEFINED) {
                    $failures[] = $fqdn . ' is a GET-Request and was expected to have parameters or an empty array for clarification';
                }

                /** @phpstan-ignore-next-line - comparing requestBody with Generator::UNDEFINED is valid */
                if ($annotation->method === 'GET' && $annotation->requestBody !== Generator::UNDEFINED) {
                    $failures[] = $fqdn . ' is a GET-Request and was not expected to have a request body';
                }
            }

            if (!\in_array($annotation->path, $routePaths, true)) {
                $failures[] = $fqdn . ' was expected to have a path of "' . \implode('" or "', $routePaths) . '", but found "' . $annotation->path . '" in OpenAPI Schema';
            }
        }

        static::assertEmpty($failures, \implode(\PHP_EOL, $failures));
    }

    public function testAllRoutesHaveSchemas(): void
    {
        $failures = [];

        foreach ($this->oa->_analysis->classes as $class => $classContext) {
            if (!\class_exists($class)) {
                static::fail('Class ' . $class . ' does not exist');
            }

            $refClass = new \ReflectionClass($class);

            foreach ($refClass->getMethods() as $method) {
                $fqdn = $class . '::' . $method->getName();

                if (\count($method->getAttributes(Route::class)) === 0 || \in_array($fqdn, self::IGNORED_ROUTES_WITHOUT_SCHEMA, true)) {
                    continue;
                }

                if (\count($method->getAttributes(Operation::class, \ReflectionAttribute::IS_INSTANCEOF)) === 0) {
                    $failures[] = $fqdn . ' is missing an OpenAPI Schema';
                }
            }
        }

        static::assertEmpty($failures, \implode(\PHP_EOL, $failures));
    }

    private function ignorableRecord(LogRecord $record): bool
    {
        foreach (self::IGNORED_LOG_MESSAGES as $message) {
            if (\str_contains($record->message, $message)) {
                return true;
            }
        }

        return false;
    }
}
