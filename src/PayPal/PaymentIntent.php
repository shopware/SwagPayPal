<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal;

final class PaymentIntent
{
    public const SALE = 'sale';
    public const AUTHORIZE = 'authorize';
    public const ORDER = 'order';

    public const INTENTS = [
        self::SALE,
        self::AUTHORIZE,
        self::ORDER,
    ];

    private function __construct()
    {
    }
}
