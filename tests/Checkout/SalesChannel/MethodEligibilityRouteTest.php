<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MethodEligibilityRouteTest extends TestCase
{
    use KernelTestBehaviour;
    use BasicTestDataBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private string $salesChannelId;

    public function setUp(): void
    {
        $methodDataRegistry = $this->getContainer()->get(PaymentMethodDataRegistry::class);
        $paymentMethodData = [];
        foreach ($methodDataRegistry->getPaymentMethods() as $paymentMethod) {
            $paymentMethodData[] = [
                'id' => $methodDataRegistry->getEntityIdFromData($paymentMethod, Context::createDefaultContext()),
                'active' => true,
            ];
        }

        $this->salesChannelId = Uuid::randomHex();
        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->salesChannelId,
            'paymentMethods' => $paymentMethodData,
            'paymentMethodId' => $methodDataRegistry->getEntityIdFromData($methodDataRegistry->getPaymentMethod(ACDCMethodData::class), Context::createDefaultContext()),
        ]);

        $systemConfig = $this->getContainer()->get(SystemConfigService::class);
        $systemConfig->set(Settings::CLIENT_ID, 'test');
        $systemConfig->set(Settings::CLIENT_SECRET, 'test');
    }

    public function testFilterPaymentMethodRoute(): void
    {
        $this->browser->request(Request::METHOD_GET, '/store-api/payment-method', ['onlyAvailable' => true]);
        $response = $this->getJsonResponse();
        static::assertContains('a_c_d_c_handler', \array_column($response['elements'], 'shortName'));

        $this->browser->request(Request::METHOD_POST, '/store-api/paypal/payment-method-eligibility', ['paymentMethods' => ['CARD', 'SEPA']]);
        static::assertSame(Response::HTTP_NO_CONTENT, $this->browser->getResponse()->getStatusCode());

        $this->browser->request(Request::METHOD_GET, '/store-api/payment-method', ['onlyAvailable' => true]);
        $response = $this->getJsonResponse();
        static::assertNotContains('a_c_d_c_handler', \array_column($response['elements'], 'shortName'));

        $this->browser->request(Request::METHOD_POST, '/store-api/paypal/payment-method-eligibility', ['paymentMethods' => []]);
        static::assertSame(Response::HTTP_NO_CONTENT, $this->browser->getResponse()->getStatusCode());

        $this->browser->request(Request::METHOD_GET, '/store-api/payment-method', ['onlyAvailable' => true]);
        $response = $this->getJsonResponse();
        static::assertContains('a_c_d_c_handler', \array_column($response['elements'], 'shortName'));
    }

    public function testFilterCart(): void
    {
        $this->browser->request(Request::METHOD_GET, '/store-api/checkout/cart');
        $response = $this->getJsonResponse();
        static::assertCount(0, $response['errors']);

        $this->browser->request(Request::METHOD_POST, '/store-api/paypal/payment-method-eligibility', ['paymentMethods' => ['CARD', 'SEPA']]);
        static::assertSame(Response::HTTP_NO_CONTENT, $this->browser->getResponse()->getStatusCode());

        $this->browser->request(Request::METHOD_GET, '/store-api/checkout/cart');
        $response = $this->getJsonResponse();
        static::assertCount(1, $response['errors']);
        static::assertSame((new PaymentMethodBlockedError(''))->getMessageKey(), \current($response['errors'])['messageKey']);

        $this->browser->request(Request::METHOD_POST, '/store-api/paypal/payment-method-eligibility', ['paymentMethods' => []]);
        static::assertSame(Response::HTTP_NO_CONTENT, $this->browser->getResponse()->getStatusCode());

        $this->browser->request(Request::METHOD_GET, '/store-api/checkout/cart');
        $response = $this->getJsonResponse();
        static::assertCount(0, $response['errors']);
    }

    private function getJsonResponse(): array
    {
        $content = $this->browser->getResponse()->getContent();
        if (!$content) {
            return [];
        }

        return \json_decode($content, true) ?? [];
    }
}
