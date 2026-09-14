<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Struct\V1\Common\LinkCollection;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PayerActionRequiredException extends PayPalApiException
{
    // not on PayPalApiException: a subclass may not narrow the visibility of an inherited constant
    public const ISSUE_PAYER_ACTION_REQUIRED = 'PAYER_ACTION_REQUIRED';

    private ?LinkCollection $links = null;

    public static function payerActionRequired(string $payPalOrderId, ?LinkCollection $links = null, ?\Throwable $previous = null): self
    {
        $exception = new self(
            ApiException::CODE_UNPROCESSABLE_ENTITY,
            \sprintf('The payer has to approve the PayPal order "%s" again before it can be captured.', $payPalOrderId),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            self::ISSUE_PAYER_ACTION_REQUIRED,
            previous: $previous,
        );

        $exception->links = $links;

        return $exception;
    }

    public function getPayerActionUrl(): ?string
    {
        foreach ($this->links ?? new LinkCollection() as $link) {
            // neither has a default and PayPal may omit either
            if (!$link->isset('rel') || !$link->isset('href')) {
                continue;
            }

            if ($link->getRel() === Link::RELATION_PAYER_ACTION) {
                return $link->getHref();
            }
        }

        return null;
    }
}
