<?php

namespace Ess\M2ePro\Model\ChangeTracker\Ebay;

use Ess\M2ePro\Model\ChangeTracker\Base\BasePriceTracker;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

class PriceTracker extends BasePriceTracker
{
    /**
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    protected function productSubQuery(): SelectQueryBuilder
    {
        $query = parent::productSubQuery();

        $query->addSelect('listing_product_id', 'lp.id');

        $syncExpression = 'IF(
          c_lp.template_synchronization_mode = 1,
          c_lp.template_synchronization_id,
          c_l.template_synchronization_id
        )';
        $query->addSelect('sync_template_id', $syncExpression);

        $sellingExpression = 'IF(
          c_lp.template_selling_format_mode = 1,
          c_lp.template_selling_format_id,
          c_l.template_selling_format_id
        )';
        $query->addSelect('selling_template_id', $sellingExpression);

        $onlinePriceExpression = 'COALESCE(c_lpv.online_price, c_lp.online_current_price, 0)';
        $query->addSelect('online_price', $onlinePriceExpression);

        $query
            ->leftJoin(
                'c_lpv',
                $this->setChannelToTableName('m2epro_%s_listing_product_variation'),
                'c_lpv.listing_product_variation_id = lpv.id'
            )
        ;

        return $query;
    }

    /**
     * @inheridoc
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getSelectQuery(): SelectQueryBuilder
    {
        $query = parent::getSelectQuery();

        $query->addSelect('calculated_price', $this->makeCalculatedPriceCondition());

        return $query;
    }

    /**
     * @return string
     * @throws \Zend_Db_Statement_Exception
     */
    protected function makeCalculatedPriceCondition(): string
    {
        $sellingPolicyQuery = $this->queryBuilder->makeSubQuery();
        $sellingPolicyQuery
            ->addSelect('id', 'template_selling_format_id')
            ->addSelect('mode', 'fixed_price_mode')
            ->addSelect('mode_attribute', 'fixed_price_custom_attribute')
            ->addSelect('modifier', 'fixed_price_modifier')
            ->addSelect('vat', ' IF(vat_mode = 1, vat_percent, 0)')
            ->from(
                't',
                $this->setChannelToTableName('m2epro_%s_template_selling_format')
            )
        ;

        $queryData = [];
        foreach ($sellingPolicyQuery->fetchAll() as $sellingPolicy) {
            $hash = hash('md5', $sellingPolicy['modifier']);
            if (array_key_exists($hash, $queryData) === false) {
                $queryData[$hash]['then'] = $this->makeCalculatedThen(
                    $sellingPolicy['modifier'],
                    $this->getPriceColumn((int)$sellingPolicy['mode'], $sellingPolicy['mode_attribute']),
                    $sellingPolicy['vat']
                );
            }
            $queryData[$hash]['when'][] = (int)$sellingPolicy['id'];
        }

        $caseBody = '';
        foreach ($queryData as $qd) {
            $ids = implode(',', $qd['when']);
            $caseBody .= " WHEN product.selling_template_id IN ($ids) THEN {$qd['then']} ";
        }

        return "CASE $caseBody END";
    }

    /**
     * @param string $json
     * @param string $priceColumn
     * @param float $vat
     *
     * @return string
     */
    protected function makeCalculatedThen(string $json, string $priceColumn, float $vat = 0.0): string
    {
        $modifiers = json_decode($json, true);
        $sql = $priceColumn;
        foreach ($modifiers as $modifier) {
            $mode = (int)$modifier['mode'];
            $value = $modifier['value'] ?? '';
            $attributeCode = $modifier['attribute_code'] ?? '';

            switch ($mode) {
                case 1:
                    $sql = "( $sql + $value)";
                    break;
                case 2:
                    $sql = "( $sql - $value )";
                    break;
                case 3:
                    $sql = "( $sql * (1+$value/100) )";
                    break;
                case 4:
                    $sql = "( $sql * (1-$value/100) )";
                    break;
                case 5:
                    $attrQ = $this->attributesQueryBuilder
                        ->getQueryForAttribute($attributeCode, 'product.product_id');
                    $sql = "( $sql + IFNULL(({$attrQ}), 0))";
                    break;
            }
        }

        return "ROUND( $sql * (1+$vat/100), 2)";
    }

    /**
     * @param int $mode
     * @param string $modeAttribute
     *
     * @return string
     */
    protected function getPriceColumn(int $mode, string $modeAttribute = ''): string
    {
        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL) {
            return '(CASE
            WHEN product.special_price IS NOT NULL
                AND product.special_from_date IS NOT NULL
                AND product.special_to_date IS NOT NULL
                AND NOW() BETWEEN product.special_from_date AND product.special_to_date
            THEN product.special_price
            WHEN product.special_price IS NOT NULL
                AND product.special_from_date IS NOT NULL
                AND product.special_from_date + INTERVAL 1 YEAR > NOW()
            THEN product.special_price
            ELSE product.price
          END)';
        }

        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributeQuery = $this->attributesQueryBuilder->getQueryForAttribute($modeAttribute);
            return "(IFNULL(($attributeQuery), product.price))";
        }

        return 'product.price';
    }
}
