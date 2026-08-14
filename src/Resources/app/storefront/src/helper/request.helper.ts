export class RequestHelper {
    /**
     * Shopware only treats a storefront request as AJAX when `XMLHttpRequest` is present.
     * Without it, a storefront route answers with full HTML pages instead of JSON:
     * an expired session redirects to the login page (which `fetch` follows, so the
     * response looks successful) and errors render the regular error template.
     * Both make the JSON parsing of the callers fail with an opaque `SyntaxError`.
     *
     * Only send this to routes declared with `XmlHttpRequest => true`.
     * Shopware rejects XHR requests to storefront routes without that flag.
     *
     * @see \Shopware\Storefront\Framework\Routing\StorefrontSubscriber::preventPageLoadingFromXmlHttpRequest
     */
    public static fetch(url: string, init: RequestInit = {}): Promise<Response> {
        const headers = new Headers(init.headers);
        headers.set('X-Requested-With', 'XMLHttpRequest');

        return fetch(url, { ...init, headers });
    }
}
