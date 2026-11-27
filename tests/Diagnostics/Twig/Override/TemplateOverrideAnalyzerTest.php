<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig\Override;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChain;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverride;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideAnalyzer;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideType;
use Swag\PayPal\Diagnostics\Twig\Parser\TwigTemplateParser;
use Twig\Environment;
use Twig\Source;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TemplateOverrideAnalyzer::class)]
class TemplateOverrideAnalyzerTest extends TestCase
{
    use KernelTestBehaviour;

    private const PATH = 'storefront/page/checkout/confirm/index.html.twig';

    private const STOREFRONT = <<<'TWIG'
        {% sw_extends '@Storefront/storefront/page/checkout/_page.html.twig' %}

        {% block page_checkout_aside_actions %}
            {% block page_checkout_confirm_tos %}{% endblock %}
            {% block page_checkout_confirm_form_submit %}
                <button>Submit order</button>
            {% endblock %}
        {% endblock %}
        TWIG;

    private const PAYPAL = <<<'TWIG'
        {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

        {% block page_checkout_confirm_form_submit %}
            {{ parent() }}

            {# @diagnostics checkout_buttons #}
            {% block page_checkout_confirm_form_submit_paypal_container %}
                <div data-swag-paypal-smart-payment-buttons></div>
            {% endblock %}
        {% endblock %}
        TWIG;

    private const CHECKED = ['page_checkout_confirm_form_submit_paypal_container' => 'checkout_buttons'];

    private TemplateOverrideAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new TemplateOverrideAnalyzer(new TwigTemplateParser($this->getTwig()));
    }

    public function testNothingToReportWhenNoTemplateComesAfterThePlugin(): void
    {
        static::assertSame([], $this->analyze([
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testNothingToReportWhenTheThemeCallsParent(): void
    {
        static::assertSame([], $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_aside_actions %}
                    <div class="custom-aside">{{ parent() }}</div>
                {% endblock %}

                {% block page_checkout_confirm_form_submit %}
                    {{ parent() }}
                    <p>Free shipping</p>
                {% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testReportsACheckedBlockOverriddenWithoutParent(): void
    {
        $overrides = $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_confirm_form_submit_paypal_container %}
                    <p>Pay on delivery only</p>
                {% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertEquals([
            new TemplateOverride(
                type: TemplateOverrideType::MissingParent,
                templatePath: self::PATH,
                namespace: 'Theme',
                file: 'custom/plugins/Theme/index.html.twig',
                line: 3,
                block: 'page_checkout_confirm_form_submit_paypal_container',
                affectedBlocks: self::CHECKED,
            ),
        ], $overrides);
    }

    public function testReportsABlockAroundACheckedBlockOverriddenWithoutParent(): void
    {
        $overrides = $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_aside_actions %}
                    <button class="btn-custom">Buy now</button>
                {% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame(TemplateOverrideType::MissingParent, $overrides[0]->type);
        static::assertSame('page_checkout_aside_actions', $overrides[0]->block);
        static::assertSame(self::CHECKED, $overrides[0]->affectedBlocks);
    }

    public function testNothingToReportWhenAnUncheckedBlockIsOverriddenWithoutParent(): void
    {
        static::assertSame([], $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_confirm_tos %}<p>Custom terms</p>{% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testNothingToReportWhenTheOverrideOfASurroundingBlockKeepsTheCheckedBlock(): void
    {
        static::assertSame([], $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_aside_actions %}
                    <div class="custom-aside">
                        {% block page_checkout_confirm_form_submit %}{{ parent() }}{% endblock %}
                    </div>
                {% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testReportsACheckedBlockKeptOnlyInsideACondition(): void
    {
        $overrides = $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_aside_actions %}
                    {% if not context.customer.guest %}
                        {% block page_checkout_confirm_form_submit %}{{ parent() }}{% endblock %}
                    {% endif %}
                {% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame(TemplateOverrideType::ConditionalBlock, $overrides[0]->type);
        static::assertSame('page_checkout_aside_actions', $overrides[0]->block);
    }

    public function testReportsParentCalledOnlyInsideACondition(): void
    {
        $overrides = $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_confirm_form_submit %}
                    {% if context.customer.guest %}
                        <button>Register first</button>
                    {% else %}
                        {{ parent() }}
                    {% endif %}
                {% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame(TemplateOverrideType::ConditionalParent, $overrides[0]->type);
        static::assertSame('page_checkout_confirm_form_submit', $overrides[0]->block);
    }

    public function testReportsTheExtensionBetweenPluginAndTheme(): void
    {
        $overrides = $this->analyze([
            'Theme' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_confirm_tos %}<p>Custom terms</p>{% endblock %}
                TWIG,
            'OtherPlugin' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_confirm_form_submit %}<button>Order with obligation to pay</button>{% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame('OtherPlugin', $overrides[0]->namespace);
        static::assertSame('page_checkout_confirm_form_submit', $overrides[0]->block);
    }

    public function testReportsATemplateThatDoesNotExtend(): void
    {
        $overrides = $this->analyze([
            'Theme' => '{% block page_checkout_main_content %}<p>One page checkout</p>{% endblock %}',
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertEquals([
            new TemplateOverride(
                type: TemplateOverrideType::TemplateReplaced,
                templatePath: self::PATH,
                namespace: 'Theme',
                file: 'custom/plugins/Theme/index.html.twig',
                line: 0,
                block: null,
                affectedBlocks: self::CHECKED,
            ),
        ], $overrides);
    }

    public function testReportsATemplateThatExtendsAnotherTemplate(): void
    {
        $overrides = $this->analyze([
            'Theme' => '{% sw_extends \'@Storefront/storefront/page/checkout/_page.html.twig\' %}',
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame(TemplateOverrideType::TemplateReplaced, $overrides[0]->type);
        static::assertSame(1, $overrides[0]->line);
    }

    public function testReportsTwigExtendsThatSkipsThePlugin(): void
    {
        $overrides = $this->analyze([
            'Theme' => '{% extends \'@Storefront/storefront/page/checkout/confirm/index.html.twig\' %}',
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame(TemplateOverrideType::PluginTemplateSkipped, $overrides[0]->type);
    }

    public function testTwigExtendsOfThePluginTemplateSkipsTheTemplatesInBetween(): void
    {
        static::assertSame([], $this->analyze([
            'Theme' => '{% extends \'@SwagPayPal/storefront/page/checkout/confirm/index.html.twig\' %}',
            'OtherPlugin' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_confirm_form_submit %}{% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testReportsExtendsOfAVariable(): void
    {
        $overrides = $this->analyze([
            'Theme' => '{% extends layout %}',
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame(TemplateOverrideType::DynamicExtends, $overrides[0]->type);
    }

    public function testIgnoresTemplatesOfOtherScopes(): void
    {
        static::assertSame([], $this->analyze([
            'Subscription' => <<<'TWIG'
                {% sw_extends {
                    template: '@Storefront/storefront/page/checkout/confirm/index.html.twig',
                    scopes: ['subscription']
                } %}

                {% block page_checkout_confirm_form_submit %}<button>Subscribe</button>{% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testOnlyTheReplacingTemplateIsReported(): void
    {
        $overrides = $this->analyze([
            'Theme' => '<p>Maintenance</p>',
            'OtherPlugin' => <<<'TWIG'
                {% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}

                {% block page_checkout_confirm_form_submit %}{% endblock %}
                TWIG,
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertCount(1, $overrides);
        static::assertSame('Theme', $overrides[0]->namespace);
        static::assertSame(TemplateOverrideType::TemplateReplaced, $overrides[0]->type);
    }

    public function testNothingToReportWithoutCheckedBlocks(): void
    {
        static::assertSame([], $this->analyze([
            'Theme' => '<p>Replaced</p>',
            'SwagPayPal' => \str_replace('{# @diagnostics checkout_buttons #}', '', self::PAYPAL),
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testReportsTemplatesTwigCanNotParse(): void
    {
        $overrides = $this->analyze([
            'Theme' => "{% sw_extends '@Storefront/storefront/page/checkout/confirm/index.html.twig' %}\n{{ unknown_function() }}",
            'SwagPayPal' => self::PAYPAL,
            'Storefront' => self::STOREFRONT,
        ]);

        static::assertEquals([
            new TemplateOverride(
                type: TemplateOverrideType::Unreadable,
                templatePath: self::PATH,
                namespace: 'Theme',
                file: 'custom/plugins/Theme/index.html.twig',
                line: 2,
                block: null,
                affectedBlocks: [],
            ),
        ], $overrides);
    }

    public function testDoesNotParseTemplatesAboveWithoutCheckedBlocks(): void
    {
        static::assertSame([], $this->analyze([
            'Theme' => '{{ unknown_function() }}',
            'SwagPayPal' => \str_replace('{# @diagnostics checkout_buttons #}', '', self::PAYPAL),
            'Storefront' => self::STOREFRONT,
        ]));
    }

    public function testTemplatesFromTheDatabaseHaveNoFile(): void
    {
        $overrides = $this->analyzer->analyze(new TemplateChain(
            self::PATH,
            [new Source('<p>Replaced</p>', '@App/' . self::PATH)],
            new Source(self::PAYPAL, '@SwagPayPal/' . self::PATH),
            [new Source(self::STOREFRONT, '@Storefront/' . self::PATH)],
        ));

        static::assertCount(1, $overrides);
        static::assertNull($overrides[0]->file);
    }

    /**
     * @param array<string, string> $templates namespace mapped to template code, most derived first
     *
     * @return list<TemplateOverride>
     */
    private function analyze(array $templates): array
    {
        $above = [];
        $plugin = null;
        $below = [];
        foreach ($templates as $namespace => $code) {
            $source = new Source($code, '@' . $namespace . '/' . self::PATH, 'custom/plugins/' . $namespace . '/index.html.twig');

            if ($namespace === 'SwagPayPal') {
                $plugin = $source;
            } elseif ($plugin === null) {
                $above[] = $source;
            } else {
                $below[] = $source;
            }
        }
        static::assertNotNull($plugin);

        return $this->analyzer->analyze(new TemplateChain(self::PATH, $above, $plugin, $below));
    }

    private function getTwig(): Environment
    {
        $twig = static::getContainer()->get('twig');
        static::assertInstanceOf(Environment::class, $twig);

        return $twig;
    }
}
