<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\Registration\WebhookRegistration;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationChecker;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationStatus;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookRegistrationChecker::class)]
#[CoversClass(WebhookRegistration::class)]
class WebhookRegistrationCheckerTest extends TestCase
{
    private const WEBHOOK_ID = 'WH-1';
    private const TOKEN = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';
    private const URL = 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=' . self::TOKEN;

    private WebhookResource&MockObject $webhookResource;

    private string $salesChannelId;

    private string $appUrl = '';

    protected function setUp(): void
    {
        $this->webhookResource = $this->createMock(WebhookResource::class);
        $this->salesChannelId = Uuid::randomHex();
    }

    public function testDisabledInTheLocalEnvironment(): void
    {
        $this->webhookResource->expects($this->never())->method('getWebhookUrl');

        $registration = $this->createChecker([Settings::IS_LOCAL_ENVIRONMENT => true, Settings::WEBHOOK_ID => self::WEBHOOK_ID])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Disabled), $registration);
    }

    public function testMissingWithoutWebhookId(): void
    {
        $this->webhookResource->expects($this->never())->method('getWebhookUrl');

        $registration = $this->createChecker([Settings::WEBHOOK_EXECUTE_TOKEN => self::TOKEN])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Missing), $registration);
    }

    public function testMissingWhenPayPalDoesntKnowTheWebhook(): void
    {
        $this->webhookResource->expects($this->once())->method('getWebhookUrl')->willThrowException(new WebhookIdInvalidException(self::WEBHOOK_ID));

        $registration = $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID, Settings::WEBHOOK_EXECUTE_TOKEN => self::TOKEN])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Missing), $registration);
    }

    public function testValid(): void
    {
        $this->webhookResource->expects($this->once())->method('getWebhookUrl')->with(self::WEBHOOK_ID, null)->willReturn(self::URL);

        $registration = $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID, Settings::WEBHOOK_EXECUTE_TOKEN => self::TOKEN])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Valid, self::URL), $registration);
    }

    public function testInvalid(): void
    {
        $registeredUrl = 'https://old-shop.example/api/_action/paypal/webhook/execute?sw-token=' . self::TOKEN;
        $this->webhookResource->expects($this->once())->method('getWebhookUrl')->willReturn($registeredUrl);

        $registration = $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID, Settings::WEBHOOK_EXECUTE_TOKEN => self::TOKEN])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Invalid, $registeredUrl), $registration);
    }

    public function testInvalidWithAnotherTokenIsOutdated(): void
    {
        $registeredUrl = 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=ffffffffffffffffffffffffffffffff';
        $this->webhookResource->method('getWebhookUrl')->willReturn($registeredUrl);

        $registration = $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID, Settings::WEBHOOK_EXECUTE_TOKEN => self::TOKEN])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Invalid, $registeredUrl, true), $registration);
    }

    public function testInvalidWithAnotherTokenIsOutdatedForTheAppUrlToo(): void
    {
        $this->appUrl = 'https://app.example';
        $registeredUrl = 'https://app.example/api/_action/paypal/webhook/execute?sw-token=ffffffffffffffffffffffffffffffff';
        $this->webhookResource->method('getWebhookUrl')->willReturn($registeredUrl);

        $registration = $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID, Settings::WEBHOOK_EXECUTE_TOKEN => self::TOKEN])->check(null);

        static::assertTrue($registration->outdated);
    }

    public function testInvalidWithoutAToken(): void
    {
        $this->webhookResource->method('getWebhookUrl')->willReturn(self::URL);

        $registration = $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Invalid, self::URL), $registration);
    }

    public function testValidWhenRegisteredForTheAppUrl(): void
    {
        $this->appUrl = 'https://app.example';
        $appUrl = 'https://app.example/api/_action/paypal/webhook/execute?sw-token=' . self::TOKEN;
        $this->webhookResource->method('getWebhookUrl')->willReturn($appUrl);

        $registration = $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID, Settings::WEBHOOK_EXECUTE_TOKEN => self::TOKEN])->check(null);

        static::assertEquals(new WebhookRegistration(WebhookRegistrationStatus::Valid, $appUrl), $registration);
    }

    public function testOtherPayPalErrorsPropagate(): void
    {
        $this->webhookResource->method('getWebhookUrl')->willThrowException(new \RuntimeException('PayPal is down'));

        $this->expectException(\RuntimeException::class);

        $this->createChecker([Settings::WEBHOOK_ID => self::WEBHOOK_ID])->check(null);
    }

    /**
     * @param array<string, mixed> $global
     * @param array<string, mixed> $salesChannel
     */
    private function createChecker(array $global, array $salesChannel = []): WebhookRegistrationChecker
    {
        $config = SystemConfigServiceMock::createWithoutCredentials($global);
        foreach ($salesChannel as $key => $value) {
            $config->set($key, $value, $this->salesChannelId);
        }

        $routes = new RouteCollection();
        $routes->add('api.action.paypal.webhook.execute', new Route('/api/_action/paypal/webhook/execute'));
        $router = new Router(new ClosureLoader(), static fn () => $routes, [], new RequestContext(host: 'shop.example', scheme: 'http'));

        $stack = new RequestStack();
        $stack->push(new Request());

        return new WebhookRegistrationChecker($this->webhookResource, $config, new WebhookUrlBuilder($router, $stack, $this->appUrl));
    }
}
