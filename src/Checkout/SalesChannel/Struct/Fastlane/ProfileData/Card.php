<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\SalesChannel\Struct\Fastlane\ProfileData\Card\PaymentSource;
use Swag\PayPal\Checkout\SalesChannel\Struct\SDKStruct;

#[Package('checkout')]
class Card extends SDKStruct
{
    protected string $id;

    protected PaymentSource $paymentSource;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getPaymentSource(): PaymentSource
    {
        return $this->paymentSource;
    }

    public function setPaymentSource(PaymentSource $paymentSource): void
    {
        $this->paymentSource = $paymentSource;
    }
}
