<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

class Ebay extends AbstractPriceCondition
{
    /**
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function loadSellingPolicyData(): array
    {
        $sellingPolicyQuery = $this->queryBuilder
            ->makeSubQuery();

        $sellingPolicyQuery
            ->addSelect('id', 'template_selling_format_id')
            ->addSelect(
                'vat',
                new \Zend_Db_Expr('IF(vat_mode = 2, vat_percent, 0)')
            )
            ->addSelect('modifier', 'fixed_price_modifier')
            ->addSelect('mode', 'fixed_price_mode')
            ->addSelect('mode_attribute', 'fixed_price_custom_attribute');

        $sellingPolicyQuery->from('t', 'm2epro_ebay_template_selling_format');

        return $sellingPolicyQuery->fetchAll();
    }
}
