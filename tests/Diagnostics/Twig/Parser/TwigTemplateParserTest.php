<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Diagnostics\Twig\Parser\ParentCall;
use Swag\PayPal\Diagnostics\Twig\Parser\TemplateStructure;
use Swag\PayPal\Diagnostics\Twig\Parser\TwigTemplateParser;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Source;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TwigTemplateParser::class)]
class TwigTemplateParserTest extends TestCase
{
    use KernelTestBehaviour;

    private const EXTENDS = "{% sw_extends '@Storefront/storefront/base.html.twig' %}\n";

    private TwigTemplateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TwigTemplateParser($this->getTwig());
    }

    public function testReadsNestedBlocksWithTheirLines(): void
    {
        $template = $this->parse(<<<'TWIG'
            {% block page %}
                {% block page_content %}
                    {% block page_content_button %}{% endblock %}
                {% endblock %}
            {% endblock %}
            TWIG);

        static::assertEqualsCanonicalizing(['page', 'page_content', 'page_content_button'], \array_keys($template->blocks));
        static::assertNull($template->blocks['page']->parentBlock);
        static::assertSame('page', $template->blocks['page_content']->parentBlock);
        static::assertSame('page_content', $template->blocks['page_content_button']->parentBlock);
        static::assertSame(3, $template->blocks['page_content_button']->line);
    }

    public function testRecognizesHowABlockCallsParent(): void
    {
        $template = $this->parse(self::EXTENDS . <<<'TWIG'
            {% block always %}<p>{{ parent() }}</p>{% endblock %}
            {% block conditional %}{% if shown %}{{ parent() }}{% endif %}{% endblock %}
            {% block looped %}{% for item in items %}{{ parent() }}{% endfor %}{% endblock %}
            {% block missing %}<p>replaced</p>{% endblock %}
            {% block captured %}{% set content %}{{ parent() }}{% endset %}{{ content|upper }}{% endblock %}
            TWIG);

        static::assertSame(ParentCall::Always, $template->blocks['always']->parentCall);
        static::assertSame(ParentCall::Conditional, $template->blocks['conditional']->parentCall);
        static::assertSame(ParentCall::Conditional, $template->blocks['looped']->parentCall);
        static::assertSame(ParentCall::None, $template->blocks['missing']->parentCall);
        static::assertSame(ParentCall::Always, $template->blocks['captured']->parentCall);
    }

    public function testUnconditionalParentCallWinsOverConditionalOne(): void
    {
        $template = $this->parse(self::EXTENDS . '{% block content %}{% if a %}{{ parent() }}{% endif %}{{ parent() }}{% endblock %}');

        static::assertSame(ParentCall::Always, $template->blocks['content']->parentCall);
    }

    public function testParentCallBelongsToTheInnermostBlock(): void
    {
        $template = $this->parse(self::EXTENDS . '{% block outer %}{% block inner %}{{ parent() }}{% endblock %}{% endblock %}');

        static::assertSame(ParentCall::None, $template->blocks['outer']->parentCall);
        static::assertSame(ParentCall::Always, $template->blocks['inner']->parentCall);
    }

    public function testBlockInsideAConditionIsConditional(): void
    {
        $template = $this->parse(self::EXTENDS . <<<'TWIG'
            {% block outer %}
                {% if a %}{% block inner %}{{ parent() }}{% endblock %}{% endif %}
                {% block after %}{% endblock %}
            {% endblock %}
            TWIG);

        static::assertTrue($template->blocks['inner']->conditional);
        static::assertSame(ParentCall::Always, $template->blocks['inner']->parentCall);
        static::assertFalse($template->blocks['after']->conditional);
    }

    public function testReadsSwExtends(): void
    {
        $template = $this->parse(self::EXTENDS);

        static::assertSame(TemplateStructure::TAG_SW_EXTENDS, $template->extendsTag);
        static::assertIsString($template->extendsTarget);
        // Shopware resolves the extended template through the inheritance chain, so only the path is fixed
        static::assertSame('storefront/base.html.twig', TemplateStructure::pathOf($template->extendsTarget));
        static::assertSame(1, $template->extendsLine);
    }

    public function testTemplateOfAnotherScopeHasNoBlocks(): void
    {
        $template = $this->parse(<<<'TWIG'
            {% sw_extends {
                template: '@Storefront/storefront/base.html.twig',
                scopes: ['subscription']
            } %}

            {% block content %}{% endblock %}
            TWIG);

        static::assertNotNull($template->extendsTarget);
        static::assertSame([], $template->blocks);
    }

    public function testReadsTwigExtends(): void
    {
        $template = $this->parse("\n{% extends '@Storefront/storefront/base.html.twig' %}");

        static::assertSame(TemplateStructure::TAG_EXTENDS, $template->extendsTag);
        static::assertSame('@Storefront/storefront/base.html.twig', $template->extendsTarget);
        static::assertSame(2, $template->extendsLine);
    }

    public function testExtendsOfAVariableHasNoTarget(): void
    {
        $template = $this->parse('{% extends ajax ? \'@Storefront/storefront/ajax.html.twig\' : \'@Storefront/storefront/base.html.twig\' %}');

        static::assertSame(TemplateStructure::TAG_EXTENDS, $template->extendsTag);
        static::assertNull($template->extendsTarget);
    }

    public function testTemplateWithoutExtends(): void
    {
        $template = $this->parse('{% block content %}{% endblock %}');

        static::assertNull($template->extendsTag);
        static::assertNull($template->extendsTarget);
    }

    public function testReadsBlocksMarkedForDiagnostics(): void
    {
        $template = $this->parse(self::EXTENDS . <<<'TWIG'
            {% block base_body_script %}
                {{ parent() }}

                {# @diagnostics funding_eligibility #}
                {% block paypal_funding_eligibility_script %}{% endblock %}

                {# @var \Swag\PayPal\Storefront\Data\Struct\FundingEligibilityData fundingEligibilityData #}
                {% block paypal_unchecked %}{% endblock %}
            {% endblock %}
            TWIG);

        static::assertSame(['paypal_funding_eligibility_script' => 'funding_eligibility'], $template->checkedBlocks);
    }

    public function testIgnoresMarkersThatAreNotDirectlyAboveABlock(): void
    {
        static::assertSame([], $this->parse(<<<'TWIG'
            {# @diagnostics express_checkout_button #}
            <div class="custom">{% block express %}{% endblock %}</div>
            {# @diagnostics Express Checkout button #}
            {% block express_described %}{% endblock %}
            TWIG)->checkedBlocks);
    }

    public function testKeepsNameAndFile(): void
    {
        $template = $this->parser->parse(new Source('', '@SwagPayPal/storefront/base.html.twig', 'custom/plugins/paypal/base.html.twig'));

        static::assertSame('@SwagPayPal/storefront/base.html.twig', $template->name);
        static::assertSame('custom/plugins/paypal/base.html.twig', $template->file);
    }

    public function testTemplatesFromTheDatabaseHaveNoFile(): void
    {
        // Shopware's loader for app templates creates sources without a path
        $template = $this->parser->parse(new Source('', '@SwagApp/storefront/base.html.twig'));

        static::assertNull($template->file);
    }

    public function testThrowsForTemplatesTwigCanNotParse(): void
    {
        $this->expectException(SyntaxError::class);

        $this->parse('{% block content %}{{ unknown_function() }}{% endblock %}');
    }

    private function parse(string $code): TemplateStructure
    {
        return $this->parser->parse(new Source($code, '@Theme/storefront/test.html.twig'));
    }

    private function getTwig(): Environment
    {
        $twig = static::getContainer()->get('twig');
        static::assertInstanceOf(Environment::class, $twig);

        return $twig;
    }
}
