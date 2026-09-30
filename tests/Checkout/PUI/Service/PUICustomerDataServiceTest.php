<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\PUI\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Swag\PayPal\Checkout\PUI\Service\PUICustomerDataService;
use Swag\PayPal\Test\Helper\FullCheckoutTrait;

/**
 * @internal
 */
#[Package('checkout')]
class PUICustomerDataServiceTest extends TestCase
{
    use FullCheckoutTrait;
    use IntegrationTestBehaviour;

    private const PHONE_NUMBER = '+491234956789';

    public function testPhoneNumberIsStoredOnBillingAddress(): void
    {
        $context = $this->registerUser();
        $order = $this->placeOrder($this->addToCart($this->createProduct(), $context), $context);

        // sorts before the billing address, so it is found first when looking at all addresses of the order
        $shippingAddressId = '00' . \substr($order->getBillingAddressId(), 2);
        $this->getOrderAddressRepository()->create([[
            'id' => $shippingAddressId,
            'orderId' => $order->getId(),
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Alice',
            'lastName' => 'Apple',
            'street' => 'Banana Boulevard 1',
            'zipcode' => '54321',
            'city' => 'Bananaville',
            'countryId' => $this->getValidCountryId(),
        ]], $context->getContext());

        $transaction = $order->getTransactions()?->first();
        static::assertNotNull($transaction);

        $this->getContainer()->get(PUICustomerDataService::class)->checkForCustomerData(
            new PaymentTransactionStruct($transaction->getId()),
            new DataBag([PUICustomerDataService::PUI_CUSTOMER_DATA_PHONE_NUMBER => self::PHONE_NUMBER]),
            $context->getContext(),
        );

        $addresses = $this->getOrderAddressRepository()->search(new Criteria([$shippingAddressId, $order->getBillingAddressId()]), $context->getContext());
        static::assertSame(self::PHONE_NUMBER, $addresses->get($order->getBillingAddressId())?->get('phoneNumber'));
        static::assertNull($addresses->get($shippingAddressId)?->get('phoneNumber'));
    }

    private function getOrderAddressRepository(): EntityRepository
    {
        return $this->getContainer()->get('order_address.repository');
    }
}
