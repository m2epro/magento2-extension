<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Ebay;

class InventoryTracker extends \Ess\M2ePro\Model\ChangeTracker\Base\AbstractInventoryTracker
{
    /**
     * @throws \Exception
     */
    protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        $query = parent::productSubQuery();

        $query->addSelect(
            'status',
            (string)(new \Zend_Db_Expr('IFNULL(c_lpv.status, lp.status)'))
        );

        $query->leftJoin(
            'c_lpv',
            $this->setChannelToTableName('m2epro_%s_listing_product_variation'),
            'c_lpv.listing_product_variation_id = lpv.id'
        );

        $query->addSelect('listing_product_id', 'lp.id');
        $query->addSelect('is_variation', 'IFNULL(c_lp.online_is_variation, 0)');

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

        $channelQtyExpression = 'IF(
          lpvo.product_id IS NULL,
          IFNULL(c_lp.online_qty, 0) - IFNULL(c_lp.online_qty_sold, 0),
          IFNULL(c_lpv.online_qty, 0) - IFNULL(c_lpv.online_qty_sold, 0)
        )';
        $query->addSelect('online_qty', $channelQtyExpression);

        $query->andWhere('c_lp.template_category_id IS NOT NULL');

        return $query;
    }
}
