<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
final class ConstantsForTesting
{
    // Constants for the repo mocks
    public const REPO_SEARCH_RESULT_TOTAL_WITH_RESULTS = 1;

    public const REPO_SEARCH_RESULT_TOTAL_WITHOUT_RESULTS = 0;

    public const VALID_CLIENT_ID = 'valid-id';
    public const INVALID_CLIENT_ID = 'invalid-id';

    public const VALID_CLIENT_SECRET = 'valid-secret';
    public const INVALID_CLIENT_SECRET = 'invalid-secret';

    public const VALID_ORDER_ID = 'valid-order-id';

    public const ORDER_ID_MISSING_PRICE = 'order-id-missing-price';

    public const ORDER_ID_MISSING_LINE_ITEMS = 'order-id-missing-line-items';

    public const PAYPAL_RESOURCE_THROWS_EXCEPTION = 'createRequestThrowsException';
    public const PAYPAL_RESPONSE_HAS_NO_APPROVAL_URL = 'noApprovalUrl';

    public const WITHOUT_TRANSACTION = 'noTransactionFound';
    public const WITHOUT_ORDER = 'noOrderFound';

    public const PAYMENT_TRANSACTION_DOMAIN = 'http://www.test.de/';

    public const TEST_ORDER_NUMBER = 'TEST_12345_TEST';
    public const TEST_ORDER_NUMBER_WITHOUT_PREFIX = '12345';
    public const TEST_ORDER_NUMBER_PREFIX = 'TEST_';
    public const TEST_ORDER_NUMBER_SUFFIX = '_TEST';
    public const TEST_ORDER_ID = 'test-order-id';
    public const EXPECTED_ITEM_NAME = 'Aerodynamic Paper Ginger Vitro';
    public const EXPECTED_PRODUCT_NUMBER = '0716562764cd43389abe16faad1838b8';
    public const EXPECTED_ITEM_CURRENCY = 'EUR';
    public const EXPECTED_ITEM_TAX = 0.0;
    public const EXPECTED_ITEM_QUANTITY = 1;
}
