<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
enum WebhookUrlSource: string
{
    /**
     * Without a request (CLI, queue) it is the APP_URL
     */
    case Administration = 'administration';
    case AppUrl = 'app_url';
}
