<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce;

use GuzzleHttp\Exception\ClientException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('checkout')]
class HoneyWebhookResult extends Struct
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $error,
        public readonly ?ClientException $exception = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        unset($data['extensions']);
        unset($data['exception']);

        return $data;
    }
}
