<?php

namespace Ess\M2ePro\Model\Amazon\Magento\Product;

class Rule extends \Ess\M2ePro\Model\Magento\Product\Rule
{
    public const NICK = 'amazon_product_rule';

    /** @var string */
    protected $nick = self::NICK;

    public function getConditionClassName(): string
    {
        return 'Amazon_Magento_Product_Rule_Condition_Combine';
    }
}
