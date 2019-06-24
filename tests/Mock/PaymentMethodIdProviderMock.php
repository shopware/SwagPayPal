<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock;

use Shopware\Core\Framework\Context;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Util\PaymentMethodIdProvider;

class PaymentMethodIdProviderMock extends PaymentMethodIdProvider
{
    public const PAYMENT_METHOD_ID = 'cfbd5018d38d41a8adcae0d94fc8bddc';

    public function __construct()
    {
        parent::__construct(new PaymentMethodRepoMock());
    }

    public function getPayPalPaymentMethodId(Context $context): string
    {
        return self::PAYMENT_METHOD_ID;
    }
}
