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

    // URLs carry tokens, e.g. the webhook's `sw-token`, the keys are spelled as `normalize()` returns them
    private const URL_KEYS = ['returnurl', 'cancelurl', 'callbackurl', 'url'];

    private const TOKEN_PARENT_KEYS = ['vault', 'token'];

    // the resource of a vault event, e.g. `VAULT.PAYMENT-TOKEN.CREATED`, is the stored payment token itself
    private const VAULT_EVENT_TYPE_PREFIX = 'VAULT.';

    // compared normalized, so `phone_number` also matches `phoneNumber`, see `normalize()`
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
        'user_name',
        // Email
        'email',
        'email_address',
        'emails',
        'primary_email',
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
        'birthday',
        // Street-level address
        'address_line_1',
        'address_line_2',
        'line1',
        'line2',
        'additional_address_line_1',
        'additional_address_line_2',
        'provided_address',
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
        'city',
        'postal_code',
        'admin_area_2',
    ];

    /**
     * @var array<string, true>|null
     */
    private static ?array $deniedKeys = null;

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

    /**
     * Vault and setup token IDs are part of the path, e.g. `/v3/vault/payment-tokens/{id}`.
     */
    public static function redactPath(string $path): string
    {
        return \preg_replace('#(/(?:payment|setup)-tokens/)[^/?]+#', '$1' . self::REDACTED, $path) ?? $path;
    }

    private static function redact(mixed $data): mixed
    {
        return self::redactLevel($data, null, true);
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

    /**
     * Lowercased and without `_`, so snake_case and camelCase keys match alike.
     */
    private static function normalize(string $key): string
    {
        return \str_replace('_', '', \mb_strtolower($key));
    }

    private static function isDenied(string $normalizedKey): bool
    {
        self::$deniedKeys ??= \array_fill_keys(\array_map(self::normalize(...), self::DENY_LIST), true);

        return isset(self::$deniedKeys[$normalizedKey]);
    }

    private static function redactLevel(mixed $data, ?string $parentKey, bool $isRoot): mixed
    {
        if (!\is_array($data)) {
            return $data;
        }

        if ($isRoot && \is_string($data['event_type'] ?? null) && \str_starts_with($data['event_type'], self::VAULT_EVENT_TYPE_PREFIX)
            && \is_array($data['resource'] ?? null) && \array_key_exists('id', $data['resource'])) {
            $data['resource']['id'] = self::REDACTED;
        }

        // a PayPal error detail names the field and echoes the submitted value, e.g. `{"field": "/payer/email", "value": "…"}`
        if (\array_key_exists('field', $data) && \array_key_exists('value', $data)) {
            $data['value'] = self::REDACTED;
        }

        // a patch sets a URL as value, e.g. the webhook update `{"op": "replace", "path": "/url", "value": "…"}`
        if (\is_string($data['path'] ?? null) && \is_string($data['value'] ?? null)
            && \in_array(self::normalize(\basename($data['path'])), self::URL_KEYS, true)) {
            $data['value'] = self::redactUrl($data['value']);
        }

        foreach ($data as $key => $value) {
            if (!\is_string($key)) {
                $data[$key] = self::redactLevel($value, $parentKey, false);

                continue;
            }

            $normalizedKey = self::normalize($key);

            // a PayPal error response names the error at its root, e.g. `UNPROCESSABLE_ENTITY`
            $isErrorName = $isRoot && $normalizedKey === 'name' && \is_string($value);

            // vault and payment tokens are stored as `{"vault": {"id": …}}` or `{"token": {"id": …}}`
            $isTokenId = $normalizedKey === 'id' && \in_array($parentKey, self::TOKEN_PARENT_KEYS, true);

            if ($isTokenId || (!$isErrorName && self::isDenied($normalizedKey))) {
                $data[$key] = self::REDACTED;

                continue;
            }

            if (\is_string($value) && \in_array($normalizedKey, self::URL_KEYS, true)) {
                $data[$key] = self::redactUrl($value);

                continue;
            }

            // HATEOAS links name the resource, e.g. `/v3/vault/payment-tokens/{id}`
            if (\is_string($value) && $normalizedKey === 'href') {
                $data[$key] = self::redactPath($value);

                continue;
            }

            $data[$key] = self::redactLevel($value, $normalizedKey, false);
        }

        return $data;
    }
}
