<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Diagnostics\DiagnosticsException;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Swag\PayPal\Diagnostics\Webhook\Api\SimulateEvent;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventGateway;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhook;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\TestWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\WebhookOutcome;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Webhook\Registration\WebhookRegistration;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationChecker;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationStatus;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Swag\PayPal\Webhook\Registration\WebhookUrlSource;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookDiagnostics
{
    // The plugin has no handler for this event type, so the test touches no order
    public const TEST_EVENT_TYPE = 'CHECKOUT.ORDER.APPROVED';

    // PayPal rejects the mock event with its default resource version 1.0 ("Not a valid Resource Type")
    public const TEST_EVENT_RESOURCE_VERSION = '2.0';

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly WebhookRegistrationChecker $registrationChecker,
        private readonly WebhookUrlBuilder $urlBuilder,
        private readonly SystemConfigService $systemConfigService,
        private readonly LastWebhookStorage $lastWebhookStorage,
        private readonly TestWebhookStorage $testWebhookStorage,
        private readonly ReachabilityProbe $reachabilityProbe,
        private readonly LoggerInterface $logger,
        private readonly ApiContextFactoryInterface $apiContextFactory,
        private readonly WebhookEventGateway $webhookEventGateway,
        private readonly ClockInterface $clock,
        private readonly MissedEventsChecker $missedEventsChecker,
    ) {
    }

    public function check(string $salesChannelId, Context $context): WebhookResult
    {
        $this->assertSalesChannelExists($salesChannelId, $context);

        $registration = $this->getRegistration($salesChannelId);
        $lastWebhook = $this->findLastWebhook($salesChannelId);

        if ($registration->status === WebhookRegistrationStatus::Disabled) {
            return new WebhookResult($registration->status, $lastWebhook, []);
        }

        $missedEvents = $registration->status === WebhookRegistrationStatus::Valid && $lastWebhook !== null
            ? $this->missedEventsChecker->check($salesChannelId, $lastWebhook)
            : null;
        $isInvalid = $registration->status === WebhookRegistrationStatus::Invalid;

        return new WebhookResult(
            $registration->status,
            $lastWebhook,
            $this->getFindings($registration, $lastWebhook, $missedEvents),
            $isInvalid && $registration->registeredUrl !== null ? WebhookUrlBuilder::maskToken($registration->registeredUrl) : null,
            $isInvalid ? $this->getCandidateUrls() : [],
            $missedEvents,
        );
    }

    public function sendTestWebhook(string $salesChannelId, WebhookTestTarget $target, Context $context): TestWebhook
    {
        $this->assertSalesChannelExists($salesChannelId, $context);

        // keys the test, as the webhook is recorded under the token of its URL
        $executeToken = $this->systemConfigService->getString(Settings::WEBHOOK_EXECUTE_TOKEN, $salesChannelId);
        if ($executeToken === '') {
            throw DiagnosticsException::noWebhookRegistered($salesChannelId);
        }

        $body = new SimulateEvent();
        $body->setEventType(self::TEST_EVENT_TYPE);
        $body->setResourceVersion(self::TEST_EVENT_RESOURCE_VERSION);

        if ($target === WebhookTestTarget::Registered) {
            $webhookId = $this->systemConfigService->getString(Settings::WEBHOOK_ID, $salesChannelId);
            if ($webhookId === '') {
                throw DiagnosticsException::noWebhookRegistered($salesChannelId);
            }

            $body->setWebhookId($webhookId);
        } else {
            // sent to PayPal only, never to the browser
            $url = $this->urlBuilder->buildAll($executeToken)[$target->value] ?? throw DiagnosticsException::noAppUrl();
            $body->setUrl($url);
        }

        // Before PayPal is asked, as it may deliver the test webhook before answering
        $sentAt = $this->clock->now();
        $event = $this->webhookEventGateway->simulateEvent($body, $this->apiContextFactory->getApiContext($salesChannelId));

        $test = new TestWebhook($event->getId(), $sentAt);
        $this->testWebhookStorage->expect($executeToken, $test);

        // it may have arrived before the test was stored
        $lastWebhook = $this->lastWebhookStorage->get($executeToken);
        if ($lastWebhook !== null && $lastWebhook->eventId === $test->eventId && $lastWebhook->at >= $sentAt) {
            $this->testWebhookStorage->recordArrival($executeToken, $test->eventId, $lastWebhook->at);
        }

        return $test;
    }

    public function hasTestWebhookArrived(string $salesChannelId, Context $context): bool
    {
        $this->assertSalesChannelExists($salesChannelId, $context);

        $executeToken = $this->systemConfigService->getString(Settings::WEBHOOK_EXECUTE_TOKEN, $salesChannelId);

        return $executeToken !== '' && $this->testWebhookStorage->hasArrived($executeToken);
    }

    /**
     * @return list<ReachabilityResult>
     */
    public function testReachability(string $salesChannelId, Context $context): array
    {
        $this->assertSalesChannelExists($salesChannelId, $context);

        $token = Uuid::randomHex();

        return $this->reachabilityProbe->probe(\array_values($this->urlBuilder->buildAll($token)), $token);
    }

    /**
     * @return list<WebhookCandidateUrl>
     */
    private function getCandidateUrls(): array
    {
        $candidates = [];
        foreach ($this->urlBuilder->buildAll(Uuid::randomHex()) as $source => $url) {
            $candidates[] = new WebhookCandidateUrl(WebhookUrlSource::from($source), WebhookUrlBuilder::maskToken($url));
        }

        return $candidates;
    }

    private function getRegistration(string $salesChannelId): WebhookRegistration
    {
        try {
            return $this->registrationChecker->check($salesChannelId);
        } catch (\Throwable $e) {
            $this->logger->warning('Could not check the webhook registration at PayPal, the diagnostics show it as unknown', Log::context(
                code: LogCode::DIAGNOSTICS_WEBHOOK_REGISTRATION_UNCHECKED,
                exception: $e,
                salesChannelId: $salesChannelId,
            ));

            return new WebhookRegistration(WebhookRegistrationStatus::Unknown);
        }
    }

    private function findLastWebhook(string $salesChannelId): ?LastWebhook
    {
        $executeToken = $this->systemConfigService->getString(Settings::WEBHOOK_EXECUTE_TOKEN, $salesChannelId);

        return $executeToken === '' ? null : $this->lastWebhookStorage->get($executeToken);
    }

    /**
     * @return list<WebhookFinding>
     */
    private function getFindings(WebhookRegistration $registration, ?LastWebhook $lastWebhook, ?MissedEvents $missedEvents): array
    {
        $findings = [];

        $registrationFinding = match ($registration->status) {
            WebhookRegistrationStatus::Missing => WebhookFindingType::RegistrationMissing,
            WebhookRegistrationStatus::Invalid => $registration->outdated ? WebhookFindingType::RegistrationOutdated : WebhookFindingType::RegistrationInvalid,
            WebhookRegistrationStatus::Unknown => WebhookFindingType::RegistrationUnknown,
            default => null,
        };
        if ($registrationFinding !== null) {
            $findings[] = new WebhookFinding($registrationFinding);
        }

        $lastWebhookFinding = match ($lastWebhook?->outcome) {
            WebhookOutcome::Failed => WebhookFindingType::LastWebhookFailed,
            WebhookOutcome::UnknownTransaction => WebhookFindingType::UnknownTransaction,
            default => null,
        };
        if ($lastWebhookFinding !== null) {
            $findings[] = new WebhookFinding($lastWebhookFinding);
        }

        if ($missedEvents !== null) {
            $findings[] = new WebhookFinding(WebhookFindingType::MissedEvents);
        }

        \usort($findings, static fn (WebhookFinding $a, WebhookFinding $b) => $a->severity->compare($b->severity));

        return $findings;
    }

    private function assertSalesChannelExists(string $salesChannelId, Context $context): void
    {
        if ($this->salesChannelRepository->searchIds(new Criteria([$salesChannelId]), $context)->getIds() === []) {
            throw SalesChannelException::salesChannelNotFound($salesChannelId);
        }
    }
}
