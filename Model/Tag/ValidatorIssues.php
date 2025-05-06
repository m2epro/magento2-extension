<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Tag;

class ValidatorIssues
{
    public const NOT_USER_ERROR = 'not-user-error';
    public const ERROR_CATEGORY_SETTINGS_NOT_SET = '0001-m2e';
    public const ERROR_QUANTITY_POLICY_CONTRADICTION = '0002-m2e';
    public const ERROR_CODE_ZERO_QTY = '0003-m2e';
    public const ERROR_CODE_ZERO_QTY_AUTO = '0004-m2e';
    public const ERROR_INVALID_VARIATIONS = '0005-m2e';
    public const ERROR_EXCEEDED_VARIATION_ATTRIBUTES = '0006-m2e';
    public const ERROR_EXCEEDED_OPTIONS_PER_ATTRIBUTE = '0007-m2e';
    public const ERROR_EXCEEDED_VARIATIONS = '0008-m2e';
    public const ERROR_CHANGE_ITEM_TYPE = '0009-m2e';
    public const ERROR_BUNDLE_OPTION_VALUE_MISSING = '0010-m2e';
    public const ERROR_DUPLICATE_OPTION_VALUES = '0011-m2e';
    public const ERROR_FIXED_PRICE_BELOW_MINIMUM = '0012-m2e';
    public const ERROR_START_PRICE_BELOW_MINIMUM = '0013-m2e';
    public const ERROR_RESERVE_PRICE_BELOW_MINIMUM = '0014-m2e';
    public const ERROR_BUY_IT_NOW_PRICE_BELOW_MINIMUM = '0015-m2e';
    public const ERROR_HIDDEN_STATUS = '0017-m2e';
    public const ERROR_DUPLICATE_PRODUCT_LISTING = '0018-m2e';
}
