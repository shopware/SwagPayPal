<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Framework\Log\Package;

/**
 * The deny list follows ShopwarePayments' `BodySanitizer`, plus credentials, card data, Shopware address keys, city and postal code.
 *
 * @internal
 */
#[Package('checkout')]
final class LogRedactor
{
    public const REDACTED = '<redacted>';

    public const REDACTED_KEYS = [
        LogKey::REQUEST,
        LogKey::RESPONSE,
        LogKey::PAYLOAD,
        LogKey::HTTP_PATH,
    ];

    private const BODY_KEYS = [LogKey::REQUEST, LogKey::RESPONSE, LogKey::PAYLOAD];

    // return, callback and webhook URLs carry tokens, e.g. the restore token or the webhook's `sw-token`
    private const URL_KEYS = ['return_url', 'cancel_url', 'callback_url', 'url'];

    private const TOKEN_PARENT_KEYS = ['vault', 'token'];

    private const DENY_LIST = [
        // Person names
        'name',
        'given_name',
        'surname',
        'full_name',
        'first_name',
        'last_name',
        'alternate_full_name',
        'suffix',
        'account_holder_name',
        'sender_name',
        'recipient_name',
        'business_name',
        'legal_name',
        // Email
        'email',
        'email_address',
        'emails',
        // Phone
        'phone',
        'phone_number',
        'national_number',
        'extension_number',
        'contact_phone',
        'shipping_phone_number',
        // Birth dates
        'birth_date',
        'date_of_birth',
        'birth_details',
        // Street-level address
        'address_line_1',
        'address_line_2',
        'line1',
        'line2',
        // Payment instrument identifiers
        'iban',
        'international_bank_account_number',
        'account_number',
        'bic',
        'bank_identifier_code',
        // Stored payment token (vaulting)
        'vault_id',
        // Government / tax identifiers
        'tax_id',
        'identification_number',
        'identification_documents',
        // Network / geo
        'ip_address',
        'geo_coordinates',
        'latitude',
        'longitude',
        // Credentials
        'access_token',
        'refresh_token',
        'id_token',
        'client_token',
        'client_secret',
        'confirm_payment_token',
        'nonce',
        'authorization',
        // Dispute-specific personal fields
        'emergency_contact',
        'allergies',
        'weight',
        'height',
        'waist',
        'chest',
        // Card data the plugin never sends but must never log
        'number',
        'security_code',
        'expiry',
        // Shopware address keys
        'firstname',
        'lastname',
        'street',
        'zipcode',
        // City and postal code
        'postal_code',
        'admin_area_2',
    ];

    /**
     * @param array<array-key, mixed> $context
     *
     * @return array<array-key, mixed>
     */
    public static function redactContext(array $context): array
    {
        foreach (self::BODY_KEYS as $key) {
            if (\array_key_exists($key, $context)) {
                $context[$key] = self::redactBody($context[$key]);
            }
        }

        if (isset($context[LogKey::HTTP_PATH]) && \is_string($context[LogKey::HTTP_PATH])) {
            $context[LogKey::HTTP_PATH] = self::redactPath($context[LogKey::HTTP_PATH]);
        }

        return $context;
    }

    /**
     * Form bodies, e.g. of the OAuth token request, have no keys to redact by, so they're replaced entirely.
     */
    public static function redactBody(mixed $body): mixed
    {
        if (\is_array($body)) {
            return self::redact($body);
        }

        if (!\is_string($body)) {
            return $body;
        }

        $decoded = \json_decode($body, true);
        if (\is_array($decoded)) {
            return self::redact($decoded);
        }

        if (\preg_match('/^[^\s=&]+=/', $body) === 1) {
            return self::REDACTED;
        }

        return $body;
    }

    private static function redact(mixed $data): mixed
    {
        return self::redactLevel($data, null, true);
    }

    /**
     * Vault and setup token IDs are part of the path, e.g. `/v3/vault/payment-tokens/{id}`.
     */
    private static function redactPath(string $path): string
    {
        return \preg_replace('#(/(?:payment|setup)-tokens/)[^/?]+#', '$1' . self::REDACTED, $path) ?? $path;
    }

    /**
     * Keeps scheme and host, so a wrong shop domain stays visible.
     */
    private static function redactUrl(string $url): string
    {
        $scheme = \parse_url($url, \PHP_URL_SCHEME);
        $host = \parse_url($url, \PHP_URL_HOST);

        return \is_string($scheme) && \is_string($host) ? \sprintf('%s://%s/%s', $scheme, $host, self::REDACTED) : self::REDACTED;
    }

    private static function redactLevel(mixed $data, ?string $parentKey, bool $isRoot): mixed
    {
        if (!\is_array($data)) {
            return $data;
        }

        // a PayPal error detail names the field and echoes the submitted value, e.g. `{"field": "/payer/email", "value": "…"}`
        if (\array_key_exists('field', $data) && \array_key_exists('value', $data)) {
            $data['value'] = self::REDACTED;
        }

        // a patch sets a URL as value, e.g. the webhook update `{"op": "replace", "path": "/url", "value": "…"}`
        if (\is_string($data['path'] ?? null) && \is_string($data['value'] ?? null)
            && \in_array(\mb_strtolower(\basename($data['path'])), self::URL_KEYS, true)) {
            $data['value'] = self::redactUrl($data['value']);
        }

        foreach ($data as $key => $value) {
            if (!\is_string($key)) {
                $data[$key] = self::redactLevel($value, $parentKey, false);

                continue;
            }

            $lowerKey = \mb_strtolower($key);

            // a PayPal error response names the error at its root, e.g. `UNPROCESSABLE_ENTITY`
            $isErrorName = $isRoot && $lowerKey === 'name' && \is_string($value);

            // vault and payment tokens are stored as `{"vault": {"id": …}}` or `{"token": {"id": …}}`
            $isTokenId = $lowerKey === 'id' && \in_array($parentKey, self::TOKEN_PARENT_KEYS, true);

            if ($isTokenId || (!$isErrorName && \in_array($lowerKey, self::DENY_LIST, true))) {
                $data[$key] = self::REDACTED;

                continue;
            }

            if (\is_string($value) && \in_array($lowerKey, self::URL_KEYS, true)) {
                $data[$key] = self::redactUrl($value);

                continue;
            }

            $data[$key] = self::redactLevel($value, $lowerKey, false);
        }

        return $data;
    }
}
