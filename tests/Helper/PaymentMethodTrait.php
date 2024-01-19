<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
trait PaymentMethodTrait
{
    protected function addPaymentMethodToDefaultsSalesChannel(string $paypalPaymentMethodId, ?Context $context = null): void
    {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

        $salesChannelRepo->update([
            [
                'id' => TestDefaults::SALES_CHANNEL,
                'paymentMethods' => [
                    $paypalPaymentMethodId => [
                        'id' => $paypalPaymentMethodId,
                    ],
                ],
            ],
        ], $context);
    }

    protected function removePaymentMethodFromDefaultsSalesChannel(string $paypalPaymentMethodId, ?Context $context = null): void
    {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $salesChannelPaymentRepo */
        $salesChannelPaymentRepo = $this->getContainer()->get('sales_channel_payment_method.repository');
        $salesChannelPaymentRepo->delete([
            [
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'paymentMethodId' => $paypalPaymentMethodId,
            ],
        ], $context);
    }
}
