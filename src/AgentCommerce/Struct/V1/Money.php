<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_money',
    required: ['currencyCode', 'value']
)]
class Money extends PayPalApiStruct
{
    /**
     * The 3-character ISO-4217 currency code that identifies the currency.
     */
    #[OA\Property(
        type: 'string',
        maxLength: 3,
        minLength: 3,
        pattern: '^[\S\s]*$'
    )]
    protected string $currencyCode;

    /**
     * The value, which might be: An integer for currencies like JPY that are not typically fractional. A decimal fraction for currencies like TND that are subdivided into thousandths. For the required number of decimal places for a currency code, see Currency Codes.
     *
     * @var numeric-string
     */
    #[OA\Property(
        type: 'string',
        maxLength: 0,
        minLength: 32,
        pattern: '^((-?[0-9]+)|(-?([0-9]+)?[.][0-9]+))$'
    )]
    protected string $value;

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): void
    {
        if (\strlen($currencyCode) !== 3) {
            throw new \InvalidArgumentException(\sprintf('Currency code "%s" is not valid.', $currencyCode));
        }

        $this->currencyCode = $currencyCode;
    }

    /**
     * @return numeric-string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param numeric-string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
