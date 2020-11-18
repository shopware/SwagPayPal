<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

trait PaymentMethodTrait
{
    protected function addPayPalToDefaultsSalesChannel(string $paypalPaymentMethodId, ?Context $context = null): void
    {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

        $salesChannelRepo->update([
            [
                'id' => Defaults::SALES_CHANNEL,
                'paymentMethods' => [
                    $paypalPaymentMethodId => [
                        'id' => $paypalPaymentMethodId,
                    ],
                ],
            ],
        ], $context);
    }

    protected function removePayPalFromDefaultsSalesChannel(string $paypalPaymentMethodId, ?Context $context = null): void
    {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepositoryInterface $salesChannelPaymentRepo */
        $salesChannelPaymentRepo = $this->getContainer()->get('sales_channel_payment_method.repository');
        $salesChannelPaymentRepo->delete([
            [
                'salesChannelId' => Defaults::SALES_CHANNEL,
                'paymentMethodId' => $paypalPaymentMethodId,
            ],
        ], $context);
    }
}
