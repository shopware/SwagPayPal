<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PaymentsApi\Administration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Swag\PayPal\PaymentsApi\Administration\Exception\RequiredParameterInvalidException;
use Swag\PayPal\PaymentsApi\Administration\PayPalPaymentController;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;
use Swag\PayPal\RestApi\V1\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V1\Resource\CaptureResource;
use Swag\PayPal\RestApi\V1\Resource\OrdersResource;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalPaymentControllerTest extends TestCase
{
    private const KEY_TO_TEST = 'keyToTest';
    private const VALUE_TO_TEST = 'valueToTest';

    private PayPalPaymentController $controller;

    /**
     * @var StaticEntityRepository<OrderCollection>
     */
    private StaticEntityRepository $orderRepository;

    protected function setUp(): void
    {
        $clientFactory = new PayPalClientFactoryMock(new NullLogger());
        $this->orderRepository = new StaticEntityRepository([]);

        $this->controller = new PayPalPaymentController(
            new PaymentResource($clientFactory),
            new SaleResource($clientFactory),
            new AuthorizationResource($clientFactory),
            new OrdersResource($clientFactory),
            new CaptureResource($clientFactory),
            $this->orderRepository,
        );
    }

    public function testGetPaymentDetails(): void
    {
        $context = Context::createDefaultContext();
        $order = new OrderEntity();
        $order->setId('testOrderId');
        $order->setSalesChannelId(Uuid::randomHex());
        $this->orderRepository->addSearch(new OrderCollection([$order]));
        $responseContent = $this->controller->paymentDetails('testOrderId', 'testPaymentId', $context)->getContent();
        static::assertNotFalse($responseContent);

        $paymentDetails = \json_decode($responseContent, true);

        static::assertSame(
            GetPaymentSaleResponseFixture::TRANSACTION_AMOUNT_DETAILS_SUBTOTAL,
            $paymentDetails['transactions'][0]['amount']['details']['subtotal']
        );
    }

    public function testGetPaymentDetailsWithInvalidOrder(): void
    {
        $context = Context::createDefaultContext();
        $this->orderRepository->addSearch(new OrderCollection());

        $this->expectException(ShopwareHttpException::class);
        $this->expectExceptionMessageMatches('/Could not find order with id \"testOrderId\"/');
        $this->controller->paymentDetails('testOrderId', 'testPaymentId', $context)->getContent();
    }

    public static function dataProviderTestResourceDetails(): array
    {
        return [
            [
                RelatedResource::AUTHORIZE,
                [
                    self::KEY_TO_TEST => 'id',
                    self::VALUE_TO_TEST => GetResourceAuthorizeResponseFixture::ID,
                ],
            ],
            [
                RelatedResource::CAPTURE,
                [
                    self::KEY_TO_TEST => 'is_final_capture',
                    self::VALUE_TO_TEST => true,
                ],
            ],
            [
                RelatedResource::ORDER,
                [
                    self::KEY_TO_TEST => 'id',
                    self::VALUE_TO_TEST => GetResourceOrderResponseFixture::ID,
                ],
            ],
            [
                RelatedResource::SALE,
                [
                    self::KEY_TO_TEST => 'id',
                    self::VALUE_TO_TEST => GetResourceSaleResponseFixture::ID,
                ],
            ],
        ];
    }

    #[DataProvider('dataProviderTestResourceDetails')]
    public function testResourceDetails(string $resourceType, array $assertions): void
    {
        $context = Context::createDefaultContext();
        $order = new OrderEntity();
        $order->setId('testOrderId');
        $order->setSalesChannelId(Uuid::randomHex());
        $this->orderRepository->addSearch(new OrderCollection([$order]));
        $responseContent = $this->controller->resourceDetails($context, $resourceType, 'testResourceId', 'testOrderId')->getContent();
        static::assertNotFalse($responseContent);

        $resource = \json_decode($responseContent, true);

        static::assertSame($assertions[self::VALUE_TO_TEST], $resource[$assertions[self::KEY_TO_TEST]]);
    }

    public function testResourceDetailsWithInvalidResourceType(): void
    {
        $context = Context::createDefaultContext();
        $order = new OrderEntity();
        $order->setId('testOrderId');
        $order->setSalesChannelId(Uuid::randomHex());
        $this->orderRepository->addSearch(new OrderCollection([$order]));
        $this->expectException(RequiredParameterInvalidException::class);
        $this->expectExceptionMessage('Required parameter "resourceType" is missing or invalid');
        $this->controller->resourceDetails($context, 'unknown', 'testResourceId', 'testOrderId')->getContent();
    }
}
