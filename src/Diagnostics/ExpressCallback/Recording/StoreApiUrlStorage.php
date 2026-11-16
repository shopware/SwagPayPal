<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback\Recording;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
class StoreApiUrlStorage
{
    // under the last callback's prefix, so uninstalling removes it
    public const KEY_PREFIX = LastCallbackStorage::KEY_PREFIX . 'store_api_url.';

    private const STORE_API_PATH = '/store-api/';

    public function __construct(
        private readonly AbstractKeyValueStorage $keyValueStorage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Never throws, recording must not break the order creation
     */
    public function record(string $salesChannelId, string $callbackUrl): void
    {
        try {
            $position = \strpos($callbackUrl, self::STORE_API_PATH);
            if ($position === false) {
                return;
            }

            // the rest holds the customer's context token
            $baseUrl = \substr($callbackUrl, 0, $position);

            if ($this->get($salesChannelId) !== $baseUrl) {
                $this->keyValueStorage->set(self::KEY_PREFIX . $salesChannelId, $baseUrl);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Could not record the Store API URL, the diagnostics can\'t test the shipping callback of the Sales Channel', Log::context(
                code: LogCode::DIAGNOSTICS_STORE_API_URL_RECORDING_FAILED,
                exception: $e,
                salesChannelId: $salesChannelId,
            ));
        }
    }

    public function get(string $salesChannelId): ?string
    {
        $value = $this->keyValueStorage->get(self::KEY_PREFIX . $salesChannelId);

        return \is_string($value) && $value !== '' ? $value : null;
    }
}
