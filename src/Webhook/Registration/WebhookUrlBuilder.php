<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookUrlBuilder
{
    private WebhookUrlSource $source = WebhookUrlSource::Administration;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly string $appUrl,
    ) {
    }

    public function build(string $token): string
    {
        return $this->buildFor($token, $this->source)
            // Without an APP_URL, the router's default host is all there is
            ?? $this->buildWithRouterContext($token);
    }

    /**
     * HTTPS with the HTTPS port, not the port of the HTTP request or the APP_URL.
     * Restores the router context, so later URLs of the request don't change.
     */
    public function buildFor(string $token, WebhookUrlSource $source): ?string
    {
        if ($source === WebhookUrlSource::Administration && $this->requestStack->getMainRequest() !== null) {
            return $this->buildWithRouterContext($token);
        }

        // The Administration without a request falls back to the APP_URL
        $uri = \trim($this->appUrl);
        if ($uri === '' || !\is_string(\parse_url($uri, \PHP_URL_HOST))) {
            return $source === WebhookUrlSource::Administration ? $this->buildWithRouterContext($token) : null;
        }

        $original = $this->router->getContext();
        $context = RequestContext::fromUri($uri);
        $context->setScheme('https');
        $this->router->setContext($context);

        try {
            return $this->generate($token);
        } finally {
            $this->router->setContext($original);
        }
    }

    /**
     * One URL per distinct URL, the Administration's first.
     *
     * @return array<value-of<WebhookUrlSource>, string>
     */
    public function buildAll(string $token): array
    {
        $urls = [];
        foreach (WebhookUrlSource::cases() as $source) {
            $url = $this->buildFor($token, $source);
            if ($url !== null && !\in_array($url, $urls, true)) {
                $urls[$source->value] = $url;
            }
        }

        return $urls;
    }

    /**
     * The token authorizes webhooks, so it must not reach the browser.
     */
    public static function maskToken(string $url): string
    {
        return \preg_replace('/([?&]' . \preg_quote(WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME, '/') . '=)[^&#]*/', '$1<token>', $url) ?? $url;
    }

    /**
     * {@see build()} uses the given source during the callback.
     *
     * @template T
     *
     * @param \Closure(): T $callback
     *
     * @return T
     */
    public function withSource(WebhookUrlSource $source, \Closure $callback): mixed
    {
        $previous = $this->source;
        $this->source = $source;

        try {
            return $callback();
        } finally {
            $this->source = $previous;
        }
    }

    private function buildWithRouterContext(string $token): string
    {
        $context = $this->router->getContext();
        $scheme = $context->getScheme();
        $context->setScheme('https');

        try {
            return $this->generate($token);
        } finally {
            $context->setScheme($scheme);
        }
    }

    private function generate(string $token): string
    {
        return $this->router->generate(
            'api.action.paypal.webhook.execute',
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
