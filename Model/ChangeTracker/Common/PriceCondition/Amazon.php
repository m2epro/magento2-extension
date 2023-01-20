<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

class Amazon extends AbstractPriceCondition
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
            ->addSelect('vat', 'regular_price_vat_percent')
            ->addSelect('modifier', 'regular_price_modifier')
            ->addSelect('mode', 'regular_price_mode')
            ->addSelect('mode_attribute', 'regular_price_custom_attribute');

        $sellingPolicyQuery->from('t', 'm2epro_amazon_template_selling_format');

        return $sellingPolicyQuery->fetchAll();
    }
}
