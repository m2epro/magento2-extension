<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

class Walmart extends AbstractPriceCondition
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
            ->addSelect('vat', 'price_vat_percent')
            ->addSelect('modifier', 'price_modifier')
            ->addSelect('mode', 'price_mode')
            ->addSelect('mode_attribute', 'price_custom_attribute');

        $sellingPolicyQuery->from('t', 'm2epro_walmart_template_selling_format');

        return $sellingPolicyQuery->fetchAll();
    }
}
