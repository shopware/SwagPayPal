<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Service;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Authentication\ApiKey;
use Swag\PayPal\Pos\Api\Exception\InvalidApiKeyException;

#[Package('checkout')]
class ApiKeyDecoder
{
    public function decode(string $jwt): ApiKey
    {
        $tks = \explode('.', $jwt);

        if (\count($tks) !== 3) {
            throw new InvalidApiKeyException('number of segments');
        }

        [$headb64, $bodyb64, $cryptob64] = $tks;

        $header = $this->convertSegment($headb64);
        if ($header === null) {
            throw new InvalidApiKeyException('header');
        }

        $payload = $this->convertSegment($bodyb64);
        if ($payload === null) {
            throw new InvalidApiKeyException('payload');
        }

        $signature = $this->decodeSegment($cryptob64);
        if ($signature === null) {
            throw new InvalidApiKeyException('signature');
        }

        $apiKey = new ApiKey();
        $apiKey->assign([
            'header' => $header,
            'payload' => $payload,
            'signature' => $signature,
        ]);

        return $apiKey;
    }

    private function convertSegment(string $base64encoded): ?array
    {
        $decoded = $this->decodeSegment($base64encoded);

        if ($decoded === null) {
            return null;
        }

        return \json_decode($decoded, true, 512, \JSON_BIGINT_AS_STRING);
    }

    private function decodeSegment(string $base64encoded): ?string
    {
        $remainder = \mb_strlen($base64encoded) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $base64encoded .= \str_repeat('=', $padlen);
        }

        $decoded = \base64_decode(\strtr($base64encoded, '-_', '+/'), true);

        return $decoded !== false ? $decoded : null;
    }
}
