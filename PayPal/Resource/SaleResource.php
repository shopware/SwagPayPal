<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Refund;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\RequestUri;

class SaleResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function refund(string $saleId, Refund $refund, Context $context): Refund
    {
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
            RequestUri::SALE_RESOURCE . '/' . $saleId . '/refund',
            $refund
        );

        $refund->assign($response);

        return $refund;
    }
}
