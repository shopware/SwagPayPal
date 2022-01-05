<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

trait FullCheckoutTrait
{
    use BasicTestDataBehaviour;

    private function createProduct(): string
    {
        /** @var EntityRepositoryInterface $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');
        $productId = Uuid::randomHex();
        $taxId = $this->getValidTaxId();

        $productRepository->upsert([[
            'id' => $productId,
            'name' => 'my product',
            'taxId' => $taxId,
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => '100',
                    'linked' => true,
                    'net' => '90',
                ],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => Defaults::SALES_CHANNEL,
                    'visibility' => 30,
                ],
            ],
            'productNumber' => $productId,
            'stock' => 100,
        ]], Context::createDefaultContext());

        return $productId;
    }

    private function registerUser(?string $email = null): SalesChannelContext
    {
        /** @var RegisterRoute $registerRoute */
        $registerRoute = $this->getContainer()->get(RegisterRoute::class);
        /** @var SalesChannelContextServiceInterface $contextService */
        $contextService = $this->getContainer()->get(SalesChannelContextService::class);
        $context = $contextService->get(new SalesChannelContextServiceParameters(Defaults::SALES_CHANNEL, Uuid::randomHex()));

        $response = $registerRoute->register(new RequestDataBag([
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Alice',
            'lastName' => 'Apple',
            'email' => $email ?? (\bin2hex(\random_bytes(8)) . '@example.com'),
            'password' => 'ilovefruits',
            'storefrontUrl' => 'default.headless0',
            'billingAddress' => [
                'street' => 'Apple Alley 42',
                'zipcode' => '1234-5',
                'city' => 'Appleton',
                'countryId' => $this->getValidCountryId(),
            ],
        ]), $context);

        $newToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);
        static::assertNotNull($newToken);

        return $contextService->get(new SalesChannelContextServiceParameters(Defaults::SALES_CHANNEL, $newToken));
    }

    private function addToCart(string $productId, SalesChannelContext $context): Cart
    {
        /** @var CartItemAddRoute $addItemRoute */
        $addItemRoute = $this->getContainer()->get(CartItemAddRoute::class);
        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);

        return $addItemRoute->add(new Request([], ['items' => [[
            'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
            'referencedId' => $productId,
        ]]]), $cartService->getCart($context->getToken(), $context), $context, null)->getCart();
    }

    private function placeOrder(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        /** @var CartOrderRoute $cartOrderRoute */
        $cartOrderRoute = $this->getContainer()->get(CartOrderRoute::class);

        return $cartOrderRoute->order($cart, $context, new RequestDataBag())->getOrder();
    }
}
