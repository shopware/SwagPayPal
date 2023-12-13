<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\SalesChannel\ClearVaultRoute;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class ClearVaultRouteTest extends TestCase
{
    public function testClearVault(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(static::once())
            ->method('delete')
            ->with(
                [[
                    'paymentMethodId' => $salesChannelContext->getPaymentMethod()->getId(),
                    'customerId' => $salesChannelContext->getCustomerId(),
                ]],
                $salesChannelContext->getContext()
            );

        $route = new ClearVaultRoute($repo);

        $route->clearVault(new Request(), $salesChannelContext);
    }
}
