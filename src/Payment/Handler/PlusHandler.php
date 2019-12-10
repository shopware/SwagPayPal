<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment\Handler;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PlusHandler extends AbstractPaymentHandler
{
    /**
     * @var PayerInfoPatchBuilder
     */
    private $payerInfoPatchBuilder;

    /**
     * @var ShippingAddressPatchBuilder
     */
    private $shippingAddressPatchBuilder;

    public function __construct(
        PaymentResource $paymentResource,
        EntityRepositoryInterface $orderTransactionRepo,
        PayerInfoPatchBuilder $payerInfoPatchBuilder,
        ShippingAddressPatchBuilder $shippingAddressPatchBuilder
    ) {
        parent::__construct($paymentResource, $orderTransactionRepo);
        $this->payerInfoPatchBuilder = $payerInfoPatchBuilder;
        $this->shippingAddressPatchBuilder = $shippingAddressPatchBuilder;
    }

    /**
     * @throws AddressNotFoundException
     * @throws AsyncPaymentProcessException
     */
    public function handlePlusPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): RedirectResponse {
        $paypalPaymentId = $dataBag->get('paypalPaymentId');
        $this->addPayPalTransactionId($transaction, $paypalPaymentId, $salesChannelContext->getContext());

        $patches = [
            $this->shippingAddressPatchBuilder->createShippingAddressPatch($customer),
            $this->payerInfoPatchBuilder->createPayerInfoPatch($customer),
        ];

        $this->patchPayPalPayment(
            $patches,
            $paypalPaymentId,
            $salesChannelContext->getSalesChannel()->getId(),
            $transaction->getOrderTransaction()->getId()
        );

        return new RedirectResponse('plusPatched');
    }
}
