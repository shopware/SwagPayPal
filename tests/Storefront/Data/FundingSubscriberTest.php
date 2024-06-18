<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Data;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\Generator;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\FundingSubscriber;
use Swag\PayPal\Storefront\Data\Service\FundingEligibilityDataService;
use Swag\PayPal\Storefront\Data\Struct\FundingEligibilityData;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class FundingSubscriberTest extends TestCase
{
    private const TEST_CLIENT_ID = 'testClientId';

    private Session $session;

    public function testGetSubscribedEvents(): void
    {
        $events = FundingSubscriber::getSubscribedEvents();

        static::assertCount(1, $events);
        static::assertSame('addFundingAvailabilityData', $events[FooterPageletLoadedEvent::class]);
    }

    public function testAddNoSettings(): void
    {
        $systemConfigService = SystemConfigServiceMock::createWithoutCredentials();
        $subscriber = $this->createSubscriber($systemConfigService);
        $event = $this->createFooterPageletLoadedEvent();
        $subscriber->addFundingAvailabilityData($event);

        static::assertFalse($event->getPagelet()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));
    }

    public function testAdd(): void
    {
        $systemConfigService = SystemConfigServiceMock::createWithoutCredentials();
        $systemConfigService->set(Settings::CLIENT_ID, self::TEST_CLIENT_ID);
        $systemConfigService->set(Settings::CLIENT_SECRET, 'testClientSecret');
        $subscriber = $this->createSubscriber($systemConfigService);
        $event = $this->createFooterPageletLoadedEvent();
        $subscriber->addFundingAvailabilityData($event);

        $extension = $event->getPagelet()->getExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION);

        static::assertInstanceOf(FundingEligibilityData::class, $extension);
        static::assertSame(self::TEST_CLIENT_ID, $extension->getClientId());
        static::assertSame('EUR', $extension->getCurrency());
        static::assertSame('en_GB', $extension->getLanguageIso());
        static::assertSame(\mb_strtolower(PaymentIntentV2::CAPTURE), $extension->getIntent());
        static::assertSame('/paypal/payment-method-eligibility', $extension->getMethodEligibilityUrl());
        static::assertSame(['SEPA'], $extension->getFilteredPaymentMethods());
    }

    private function createSubscriber(SystemConfigService $systemConfig): FundingSubscriber
    {
        $credentialsUtil = new CredentialsUtil($systemConfig);

        $localeCodeProvider = $this->createMock(LocaleCodeProvider::class);
        $localeCodeProvider->method('getFormattedLocaleCode')->willReturn('en_GB');

        $router = $this->createMock(RouterInterface::class);
        $router->expects(static::atMost(1))->method('generate')->willReturn('/paypal/payment-method-eligibility');

        $this->session = new Session(new MockArraySessionStorage());
        $this->session->set(MethodEligibilityRoute::SESSION_KEY, [SEPAHandler::class]);
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new FundingSubscriber(
            new SettingsValidationService($systemConfig, new NullLogger()),
            new FundingEligibilityDataService(
                $credentialsUtil,
                $systemConfig,
                $localeCodeProvider,
                $router,
                $requestStack
            )
        );
    }

    private function createFooterPageletLoadedEvent(): FooterPageletLoadedEvent
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        return new FooterPageletLoadedEvent(
            new FooterPagelet(null),
            $salesChannelContext,
            new Request()
        );
    }
}
