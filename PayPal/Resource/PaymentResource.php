<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\Component\Patch\PatchInterface;
use SwagPayPal\PayPal\RequestUri;
use SwagPayPal\PayPal\Struct\Payment;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function create(Payment $payment, Context $context): Payment
    {
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendRequest(
            Request::METHOD_POST,
            RequestUri::PAYMENT_RESOURCE,
            $payment->toArray()
        );

        return Payment::fromArray($response);
    }

    public function execute(string $payerId, string $paymentId, Context $context): Payment
    {
        $requestData = ['payer_id' => $payerId];
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendRequest(
            Request::METHOD_POST,
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId . '/execute',
            $requestData
        );

        return Payment::fromArray($response);
    }

    public function get(string $paymentId, Context $context): Payment
    {
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendRequest(
            Request::METHOD_GET,
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId
        );

        return Payment::fromArray($response);
    }

    /**
     * @param PatchInterface[] $patches
     */
    public function patch(string $paymentId, array $patches, Context $context): void
    {
        $requestData = [];
        foreach ($patches as $patch) {
            $requestData[] = [
                'op' => $patch->getOperation(),
                'path' => $patch->getPath(),
                'value' => $patch->getValue(),
            ];
        }

        $this->payPalClientFactory->createPaymentClient($context)->sendRequest(
            Request::METHOD_PATCH,
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId,
            $requestData
        );
    }
}
