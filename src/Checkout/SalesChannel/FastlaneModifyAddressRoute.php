<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractUpsertAddressRoute;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\Checkout\Fastlane\AddressConverter;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Address;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Name;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\PhoneNumber;
use Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class FastlaneModifyAddressRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractUpsertAddressRoute $upsertAddressRoute,
        private readonly AddressConverter $addressConverter,
    ) {
    }

    public function getDecorated(): self
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/paypal/fastlane/modify-address', name: 'store-api.paypal.fastlane.modify-address', methods: ['POST'])]
    public function modifyAddress(SalesChannelContext $salesChannelContext, Request $request): Response
    {
        $address = (new Address())->assign($request->request->all('address'));
        $name = (new Name())->assign($request->request->all('name'));
        $phoneNumber = (new PhoneNumber())->assign($request->request->all('phoneNumber'));
        $id = $request->request->getString('id');

        $address = $this->addressConverter->convertAddressData($address, $name, $phoneNumber, $salesChannelContext->getContext());

        $this->upsertAddressRoute->upsert($id, new RequestDataBag($address), $salesChannelContext, $salesChannelContext->getCustomer());

        return new NoContentResponse();
    }
}
