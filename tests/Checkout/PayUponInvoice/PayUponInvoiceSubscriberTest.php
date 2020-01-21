<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\PayUponInvoice;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\PayUponInvoice\PayUponInvoiceSubscriber;
use Swag\PayPal\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;

class PayUponInvoiceSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    protected function setUp(): void
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);

        $this->salesChannelContext = $salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $payUponInvoiceSubscriber = $this->getSubscriber();

        $expectedResult = [
            'sales_channel.payment_method.search.result.loaded' => ['onSearchResultLoaded', -1],
        ];

        static::assertSame($expectedResult, $payUponInvoiceSubscriber::getSubscribedEvents());
    }

    public function testOnSearchResultLoadedWithoutPuiPaymentMethodInCollection(): void
    {
        $event = $this->getEvent(false);

        $this->getSubscriber()->onSearchResultLoaded($event);

        static::assertCount(
            0,
            $event->getResult()->getEntities()->filterByProperty('handlerIdentifier', PayPalPuiPaymentHandler::class)
        );
    }

    public function testOnSearchResultInvalidSettings(): void
    {
        $event = $this->getEvent();

        $this->getSubscriber()->onSearchResultLoaded($event);

        static::assertCount(
            1,
            $event->getResult()->getEntities()->filterByProperty('handlerIdentifier', PayPalPuiPaymentHandler::class)
        );
    }

    public function testOnSearchResultRemovesPuiPaymentMethodIfSpcCheckoutIsDisabled(): void
    {
        $settings = $this->getSettingsStruct(false);

        $event = $this->getEvent();

        $this->getSubscriber($settings)->onSearchResultLoaded($event);

        static::assertCount(
            0,
            $event->getResult()->getEntities()->filterByProperty('handlerIdentifier', PayPalPuiPaymentHandler::class)
        );
    }

    public function testOnSearchResultRemovesPuiPaymentMethodIfAdvancedSpbPaymentsAreDisabled(): void
    {
        $settings = $this->getSettingsStruct(true, false);
        $event = $this->getEvent();

        $this->getSubscriber($settings)->onSearchResultLoaded($event);

        static::assertCount(
            0,
            $event->getResult()->getEntities()->filterByProperty('handlerIdentifier', PayPalPuiPaymentHandler::class)
        );
    }

    public function testOnSearchResultDoesNotRemovesPuiPaymentMethodIfSpbIsFullyEnabled(): void
    {
        $settings = $this->getSettingsStruct();
        $event = $this->getEvent();

        $this->getSubscriber($settings)->onSearchResultLoaded($event);

        static::assertCount(
            1,
            $event->getResult()->getEntities()->filterByProperty('handlerIdentifier', PayPalPuiPaymentHandler::class)
        );
    }

    private function getSubscriber(?SwagPayPalSettingStruct $settings = null): PayUponInvoiceSubscriber
    {
        return new PayUponInvoiceSubscriber(new SettingsServiceMock($settings));
    }

    private function getSettingsStruct(
        bool $spbCheckoutEnabled = true,
        bool $alternativeSpbMethodsEnabled = true
    ): SwagPayPalSettingStruct {
        $randomHex = Uuid::randomHex();

        $data = [
            'clientId' => $randomHex,
            'clientSecret' => $randomHex,
            'spbCheckoutEnabled' => $spbCheckoutEnabled,
            'spbAlternativePaymentMethodsEnabled' => $alternativeSpbMethodsEnabled,
        ];

        $settings = new SwagPayPalSettingStruct();
        $settings->assign($data);

        return $settings;
    }

    private function getEvent(bool $puiInPaymentMethodCollection = true): SalesChannelEntitySearchResultLoadedEvent
    {
        $collection = new PaymentMethodCollection();

        foreach ($this->getPaymentMethodEntities($puiInPaymentMethodCollection) as $entity) {
            $collection->add($entity);
        }

        return new SalesChannelEntitySearchResultLoadedEvent(
            new PaymentMethodDefinition(),
            new EntitySearchResult(
                $collection->count(),
                $collection,
                null,
                new Criteria(),
                Context::createDefaultContext()
            ),
            $this->salesChannelContext
        );
    }

    /**
     * @return PaymentMethodEntity[]
     */
    private function getPaymentMethodEntities(bool $withPuiPaymentMethod): array
    {
        $paymentMethods = [];
        $fakePaymentMethod = new PaymentMethodEntity();
        $fakePaymentMethod->assign([
            'id' => Uuid::randomHex(),
            'name' => 'test',
            'active' => true,
            'handlerIdentifier' => 'SomeHandler',
        ]);

        $paymentMethods[] = $fakePaymentMethod;

        if ($withPuiPaymentMethod) {
            $paymentMethods[] = $this->getPuiPaymentMethodEntity();
        }

        return $paymentMethods;
    }

    private function getPuiPaymentMethodEntity(): PaymentMethodEntity
    {
        $pui = new PaymentMethodEntity();
        $data = [
            'id' => Uuid::randomHex(),
            'handlerIdentifier' => PayPalPuiPaymentHandler::class,
            'active' => true,
        ];
        $pui->assign($data);

        return $pui;
    }
}
