<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class ExistingPosAccountException extends ShopwareHttpException
{
    /**
     * @param array<string, string> $salesChannelNames
     */
    public function __construct(array $salesChannelNames)
    {
        parent::__construct(
            'This Zettle account has already been configured in the Sales Channel {{ salesChannelIds }}.',
            ['salesChannelIds' => \implode(', ', \array_map(static function ($id, $name) {
                return \sprintf('"%s": %s', $name, $id);
            }, \array_keys($salesChannelNames), $salesChannelNames))]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_POS__EXISTING_POS_ACCOUNT';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }
}
