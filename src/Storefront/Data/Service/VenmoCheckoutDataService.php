<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Storefront\Data\Struct\VenmoCheckoutData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\VenmoMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class VenmoCheckoutDataService extends AbstractCheckoutDataService
{
    /**
     * @internal
     */
    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        private readonly VaultDataService $vaultDataService,
    ) {
        parent::__construct($paymentMethodDataRegistry, $localeCodeProvider, $router, $systemConfigService, $credentialsUtil);
    }

    public function buildCheckoutData(SalesChannelContext $context, ?Cart $cart = null, ?OrderEntity $order = null): ?VenmoCheckoutData
    {
        $data = $this->getBaseData($context, $order);

        return (new VenmoCheckoutData())->assign(\array_merge($data, [
            'userIdToken' => $this->vaultDataService->getUserIdToken($context),
        ]));
    }

    public function getMethodDataClass(): string
    {
        return VenmoMethodData::class;
    }
}
