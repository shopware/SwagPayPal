<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Setting\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class SystemConfigServiceMock extends SystemConfigService
{
    /**
     * @var mixed[][]
     */
    private $data = [];

    /**
     * @return mixed|null
     */
    public function get(string $key, ?string $salesChannelId = null, bool $inherit = true)
    {
        $salesChannelId = (string) $salesChannelId;
        if (!isset($this->data[$salesChannelId][$key])) {
            return null;
        }

        return $this->data[$salesChannelId][$key] ?? null;
    }

    public function getDomain(string $domain, ?string $salesChannelId = null, bool $inherit = false): array
    {
        $values = [];
        $domain = rtrim($domain, '.') . '.';

        if ($inherit && $salesChannelId !== null) {
            foreach ($this->data[''] as $key => $value) {
                if (mb_strpos($key, $domain) === 0) {
                    $values[$key] = $value;
                }
            }
        }
        $salesChannelId = (string) $salesChannelId;
        if (!isset($this->data[$salesChannelId])) {
            return $values;
        }

        foreach ($this->data[$salesChannelId] as $key => $value) {
            if (mb_strpos($key, $domain) === 0) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    public function set(string $key, $value, ?string $salesChannelId = null): void
    {
        $salesChannelId = (string) $salesChannelId;
        if (!isset($this->data[$salesChannelId])) {
            $this->data[$salesChannelId] = [];
        }
        $this->data[$salesChannelId][$key] = $value;
    }

    public function delete(string $key, ?string $salesChannel = null): void
    {
        $this->set($key, null, $salesChannel);
    }
}
