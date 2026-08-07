<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Psr\Clock\ClockInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Remembers where an interrupted payment can be resumed, in the storefront session it was interrupted in.
 */
#[Package('checkout')]
class PaymentResumeService
{
    public const SESSION_KEY = 'swagPayPalPaymentResume';

    private const RESUME_LIMIT = 8;

    private const KEY_RESUME_URL = 'resumeUrl';
    private const KEY_EXPIRES_AT = 'expiresAt';

    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly ClockInterface $clock,
    ) {
    }

    public function store(SessionInterface $session, string $paypalOrderId, string $resumeUrl, string $salesChannelId): void
    {
        $resumes = $this->read($session);
        unset($resumes[$paypalOrderId]);
        $resumes[$paypalOrderId] = [
            self::KEY_RESUME_URL => $resumeUrl,
            self::KEY_EXPIRES_AT => $this->clock->now()->getTimestamp() + $this->getLifetime($salesChannelId),
        ];

        // a checkout may create any number of PayPal orders, of which only the most recent ones can still be resumed
        $session->set(self::SESSION_KEY, \array_slice($resumes, -self::RESUME_LIMIT, null, true));
    }

    /**
     * Removes the resume it returns, because the finalize URL it points to is single-use.
     */
    public function consume(SessionInterface $session, string $paypalOrderId): ?string
    {
        $resumes = $this->read($session);
        $resume = $resumes[$paypalOrderId] ?? null;
        if ($resume === null) {
            return null;
        }

        unset($resumes[$paypalOrderId]);
        $session->set(self::SESSION_KEY, $resumes);

        if (!\is_array($resume)) {
            return null;
        }

        $resumeUrl = $resume[self::KEY_RESUME_URL] ?? null;
        $expiresAt = $resume[self::KEY_EXPIRES_AT] ?? null;
        if (!\is_string($resumeUrl) || $resumeUrl === '' || !\is_int($expiresAt)) {
            return null;
        }

        return $expiresAt >= $this->clock->now()->getTimestamp() ? $resumeUrl : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(SessionInterface $session): array
    {
        $resumes = $session->get(self::SESSION_KEY, []);

        return \is_array($resumes) ? $resumes : [];
    }

    /**
     * A resume is worthless once the payment can no longer be finalized, and must not expire before that.
     *
     * @see \Shopware\Core\Checkout\Payment\Cart\Token\PaymentTokenGenerator::getTokenLifetime()
     */
    private function getLifetime(string $salesChannelId): int
    {
        return ($this->systemConfigService->getInt('core.cart.paymentFinalizeTransactionTime', $salesChannelId) * 60) ?: 1800;
    }
}
