<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Ebay;

class PriceTracker extends \Ess\M2ePro\Model\ChangeTracker\Base\AbstractPriceTracker
{
    /**
     * @return string
     */
    protected function getOnlinePriceCondition(): string
    {
        return 'COALESCE(c_lpv.online_price, c_lp.online_current_price, 0)';
    }

    protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
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

        $query
            ->leftJoin(
                'c_lpv',
                $this->setChannelToTableName('m2epro_%s_listing_product_variation'),
                'c_lpv.listing_product_variation_id = lpv.id'
            );

        return $query;
    }

    protected function getMarketplaceCurrencyField(): string
    {
        return 'currency';
    }
}
