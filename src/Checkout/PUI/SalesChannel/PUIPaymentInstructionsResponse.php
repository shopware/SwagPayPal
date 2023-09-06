<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\SalesChannel;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;

#[Package('checkout')]
class PUIPaymentInstructionsResponse extends StoreApiResponse
{
    protected PayUponInvoice $paymentInstructions;

    public function __construct(PayUponInvoice $paymentInstructions)
    {
        $this->paymentInstructions = $paymentInstructions;
        parent::__construct(new ArrayStruct(['paymentInstructions' => $paymentInstructions]));
    }

    public function getPaymentInstructions(): PayUponInvoice
    {
        return $this->paymentInstructions;
    }
}
