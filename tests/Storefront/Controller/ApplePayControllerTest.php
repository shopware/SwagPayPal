<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Storefront\Controller\ApplePayController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class ApplePayControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private CredentialsUtil&MockObject $credentialsUtil;

    private SalesChannelContext $context;

    private const EXPECTED_PATH = '/.well-known/apple-developer-merchantid-domain-association';

    protected function setUp(): void
    {
        $this->credentialsUtil = $this->createMock(CredentialsUtil::class);
        $this->context = Generator::createSalesChannelContext();
    }

    public function testLiveDomainAssociation(): void
    {
        $this->credentialsUtil
            ->expects(static::once())
            ->method('isSandbox')
            ->with($this->context->getSalesChannelId())
            ->willReturn(false);

        $controller = new ApplePayController($this->credentialsUtil);

        $actualToken = $controller->getApplePayDomainAssociation($this->context);

        static::assertSame(Response::HTTP_OK, $actualToken->getStatusCode());
        static::assertSame($controller::LIVE_TOKEN, $actualToken->getContent());
    }

    public function testSandboxDomainAssociation(): void
    {
        $this->credentialsUtil
            ->expects(static::once())
            ->method('isSandbox')
            ->with($this->context->getSalesChannelId())
            ->willReturn(true);

        $controller = new ApplePayController($this->credentialsUtil);

        $actualToken = $controller->getApplePayDomainAssociation($this->context);

        static::assertSame(Response::HTTP_OK, $actualToken->getStatusCode());
        static::assertSame($controller::SANDBOX_TOKEN, $actualToken->getContent());
    }

    public function testDomainPath(): void
    {
        $router = $this->getContainer()->get('router');

        $actualPath = $router->generate('paypal.apple_pay.domain_association');

        static::assertSame(self::EXPECTED_PATH, $actualPath);
    }
}
