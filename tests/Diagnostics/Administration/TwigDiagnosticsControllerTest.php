<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Administration;

use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Diagnostics\Administration\TwigDiagnosticsController;
use Swag\PayPal\Diagnostics\Severity;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverride;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideType;
use Swag\PayPal\Diagnostics\Twig\TemplateArea;
use Swag\PayPal\Diagnostics\Twig\TemplateAreaResult;
use Swag\PayPal\Diagnostics\Twig\TwigDiagnostics;
use Swag\PayPal\Diagnostics\Twig\TwigDiagnosticsResult;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TwigDiagnosticsController::class)]
#[CoversClass(TemplateOverride::class)]
class TwigDiagnosticsControllerTest extends TestCase
{
    public function testReturnsTheResultOfTheCheck(): void
    {
        $twigDiagnostics = $this->createMock(TwigDiagnostics::class);
        $twigDiagnostics->expects($this->once())
            ->method('check')
            ->with(TestDefaults::SALES_CHANNEL, Context::createDefaultContext())
            ->willReturn(new TwigDiagnosticsResult(
                storefront: new TemplateAreaResult(applicable: true, overrides: [
                    new TemplateOverride(
                        type: TemplateOverrideType::MissingParent,
                        templatePath: 'storefront/layout/footer/footer.html.twig',
                        namespace: 'Theme',
                        file: 'custom/plugins/Theme/footer.html.twig',
                        line: 3,
                        block: 'layout_footer_payment_logos',
                        affectedBlocks: ['layout_footer_payment_logos_paypal_installment_banner' => 'pay_later_banner'],
                    ),
                ]),
                documents: new TemplateAreaResult(applicable: true, overrides: []),
            ));

        $response = (new TwigDiagnosticsController($twigDiagnostics))->check(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());

        static::assertEquals([
            'storefront' => [
                'applicable' => true,
                'overrides' => [[
                    'type' => 'missing_parent',
                    'severity' => 'error',
                    'templatePath' => 'storefront/layout/footer/footer.html.twig',
                    'namespace' => 'Theme',
                    'file' => 'custom/plugins/Theme/footer.html.twig',
                    'line' => 3,
                    'block' => 'layout_footer_payment_logos',
                    'affected' => [['block' => 'layout_footer_payment_logos_paypal_installment_banner', 'feature' => 'pay_later_banner']],
                    'extensions' => [],
                ]],
                'extensions' => [],
            ],
            'documents' => [
                'applicable' => true,
                'overrides' => [],
                'extensions' => [],
            ],
            'extensions' => [],
        ], \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testSchemaListsEveryOverrideTypeAndSeverity(): void
    {
        $openApi = (new Generator(new NullLogger()))->generate([__DIR__ . '/../../../src/Diagnostics'], validate: false);
        static::assertInstanceOf(OpenApi::class, $openApi);

        $spec = \json_decode($openApi->toJson(), true, flags: \JSON_THROW_ON_ERROR);
        $override = $spec['components']['schemas']['swag_paypal_diagnostics_twig_template_override'];

        static::assertSame(\array_column(TemplateOverrideType::cases(), 'value'), $override['properties']['type']['enum']);
        static::assertSame(\array_column(Severity::cases(), 'value'), $override['properties']['severity']['enum']);
    }

    public function testResponseHasAResultForEveryArea(): void
    {
        $openApi = (new Generator(new NullLogger()))->generate([__DIR__ . '/../../../src/Diagnostics'], validate: false);
        static::assertInstanceOf(OpenApi::class, $openApi);

        $spec = \json_decode($openApi->toJson(), true, flags: \JSON_THROW_ON_ERROR);

        static::assertSame(
            \array_column(TemplateArea::cases(), 'value'),
            $spec['components']['schemas']['swag_paypal_diagnostics_twig_result']['required'],
        );
    }
}
