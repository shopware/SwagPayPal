<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagPayPal\PayPal\Client\Exception\PayPalSettingsInvalidException;
use SwagPayPal\PayPal\Client\PayPalClient;
use SwagPayPal\PayPal\Component\Patch\PatchInterface;
use SwagPayPal\PayPal\RequestUri;
use SwagPayPal\PayPal\Struct\Payment;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var RepositoryInterface
     */
    private $settingGeneralRepo;

    public function __construct(TokenResource $tokenResource, RepositoryInterface $settingGeneralRepo)
    {
        $this->tokenResource = $tokenResource;
        $this->settingGeneralRepo = $settingGeneralRepo;
    }

    public function create(Payment $payment, Context $context): Payment
    {
        $paypalClient = $this->createPaymentClient($context);
        $response = $paypalClient->sendRequest(Request::METHOD_POST, RequestUri::PAYMENT_RESOURCE, $payment->toArray());

        return Payment::fromArray($response);
    }

    public function execute(string $payerId, string $paymentId, Context $context): Payment
    {
        $paypalClient = $this->createPaymentClient($context);
        $requestData = ['payer_id' => $payerId];

        $response = $paypalClient->sendRequest(
            Request::METHOD_POST,
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId . '/execute',
            $requestData
        );

        return Payment::fromArray($response);
    }

    public function get(string $paymentId, Context $context): Payment
    {
        $paypalClient = $this->createPaymentClient($context);
        $response = $paypalClient->sendRequest(
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
        $paypalClient = $this->createPaymentClient($context);
        $requestData = [];
        foreach ($patches as $patch) {
            $requestData[] = [
                'op' => $patch->getOperation(),
                'path' => $patch->getPath(),
                'value' => $patch->getValue(),
            ];
        }

        $paypalClient->sendRequest(
            Request::METHOD_PATCH,
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId,
            $requestData
        );
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function createPaymentClient(Context $context): PayPalClient
    {
        /** @var SwagPayPalSettingGeneralCollection $settingsCollection */
        $settingsCollection = $this->settingGeneralRepo->search(new Criteria(), $context)->getEntities();
        $settings = $settingsCollection->first();

        return new PayPalClient($this->tokenResource, $context, $settings);
    }
}
