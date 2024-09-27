<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Payment;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class AsyncPaymentHandlerMock implements AsynchronousPaymentHandlerInterface
{
    public const REQUEST_PARAM_SHOULD_THROW_EXCEPTION = 'throwException';

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
    ): RedirectResponse {
        return new RedirectResponse('');
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext,
    ): void {
        $transactionId = $transaction->getOrderTransaction()->getId();
        if ($request->query->getBoolean(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_CANCEL)) {
            throw PaymentException::customerCanceled(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
        }

        if ($request->query->getBoolean(self::REQUEST_PARAM_SHOULD_THROW_EXCEPTION)) {
            throw PaymentException::asyncFinalizeInterrupted($transactionId, 'Test error message');
        }
    }
}
