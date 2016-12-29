<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Indexer\Listing\Product;

class VariationParent extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_indexer_listing_product_variation_parent', 'listing_product_id');
    }

    //########################################

    public function build($listingId, $component)
    {
        if (!in_array($component, [
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Helper\Component\Amazon::NICK
        ])) {
            throw new \Ess\M2ePro\Model\Exception\Logic("Wrong component provided [{$component}]");
        }

        $select = $component == \Ess\M2ePro\Helper\Component\Amazon::NICK
            ? $this->getBuildIndexForAmazonSelect($listingId)
            : $this->getBuildIndexForEbaySelect($listingId);

        $createDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $createDate = $createDate->format('Y-m-d H:i:s');

        $select->columns([
            new \Zend_Db_Expr($this->getConnection()->quote($component)),
            new \Zend_Db_Expr($this->getConnection()->quote($listingId)),
            new \Zend_Db_Expr($this->getConnection()->quote($createDate))
        ]);

        $query = $this->getConnection()->insertFromSelect(
            $select,
            $this->getMainTable(),
            [
                'listing_product_id',
                'min_price',
                'max_price',
                'component_mode',
                'listing_id',
                'create_date'
            ],
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
        );
        $this->getConnection()->query($query);
    }

    public function clear($listingId = null)
    {
        $conditions = [];
        $listingId && $conditions['listing_id = ?'] = (int)$listingId;

        $this->getConnection()->delete($this->getMainTable(), $conditions);
    }

    //########################################

    public function getBuildIndexForAmazonSelect($listingId)
    {
        $select = $this->getConnection()->select()
            ->from(
                [
                    'malp' => $this->activeRecordFactory->getObject('Amazon\Listing\Product')
                        ->getResource()->getMainTable()
                ],
                [
                    'variation_parent_id',
                    new \Zend_Db_Expr(
                        "MIN(
                            IF(
                                malp.online_sale_price_start_date IS NOT NULL AND
                                malp.online_sale_price_end_date IS NOT NULL AND
                                malp.online_sale_price_start_date <= CURRENT_DATE() AND
                                malp.online_sale_price_end_date >= CURRENT_DATE(),
                                malp.online_sale_price,
                                malp.online_price
                            )
                        ) as variation_min_price"
                    ),
                    new \Zend_Db_Expr(
                        "MAX(
                            IF(
                                malp.online_sale_price_start_date IS NOT NULL AND
                                malp.online_sale_price_end_date IS NOT NULL AND
                                malp.online_sale_price_start_date <= CURRENT_DATE() AND
                                malp.online_sale_price_end_date >= CURRENT_DATE(),
                                malp.online_sale_price,
                                malp.online_price
                            )
                        ) as variation_max_price"
                    )
                ]
            )
            ->joinInner(
                ['mlp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()],
                'malp.listing_product_id = mlp.id',
                []
            )
            ->where('mlp.status IN (?)', [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN
            ])
            ->where('mlp.listing_id = ?', (int)$listingId)
            ->where('malp.variation_parent_id IS NOT NULL')
            ->group('malp.variation_parent_id');

        return $select;
    }

    public function getBuildIndexForEbaySelect($listingId)
    {
        $select = $this->getConnection()->select()
            ->from(
                [
                    'mlpv' => $this->activeRecordFactory->getObject('Listing\Product\Variation')
                        ->getResource()->getMainTable()
                ],
                [
                    'listing_product_id'
                ]
            )
            ->joinInner(
                [
                    'melpv' => $this->activeRecordFactory->getObject('Ebay\Listing\Product\Variation')
                        ->getResource()->getMainTable()
                ],
                'mlpv.id = melpv.listing_product_variation_id',
                [
                    new \Zend_Db_Expr('MIN(`melpv`.`online_price`) as variation_min_price'),
                    new \Zend_Db_Expr('MAX(`melpv`.`online_price`) as variation_max_price')
                ]
            )
            ->joinInner(
                ['mlp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()],
                'mlpv.listing_product_id = mlp.id',
                []
            )
            ->where('mlp.listing_id = ?', (int)$listingId)
            ->where('melpv.status != ?', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)
            ->group('mlpv.listing_product_id');

        return $select;
    }

    //########################################
}