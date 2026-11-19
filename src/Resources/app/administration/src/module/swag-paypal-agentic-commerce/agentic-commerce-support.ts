// Agentic commerce needs the JWT support of the core, which was introduced with Shopware 6.6.9.0.
// Without it, the plugin registers neither its services nor its routes (see `services.php` and `routes.php`).
const MIN_SHOPWARE_VERSION = [6, 6, 9, 0];

export function isAgenticCommerceSupported(): boolean {
    const match = /^(\d+)\.(\d+)\.(\d+)\.(\d+)/.exec(Shopware.Context.app.config.version ?? '');

    // The Administration loads the version after the login, so it can be unknown for a moment
    if (!match) {
        return true;
    }

    for (const [index, minimum] of MIN_SHOPWARE_VERSION.entries()) {
        const part = Number(match[index + 1]);

        if (part !== minimum) {
            return part > minimum;
        }
    }

    return true;
}
