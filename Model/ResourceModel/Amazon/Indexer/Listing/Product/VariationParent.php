<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Indexer\Listing\Product;

class VariationParent extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_indexer_listing_product_variation_parent', 'listing_product_id');
    }

    //########################################

    public function getTrackedFields()
    {
        return array(
            'online_price',
            'online_sale_price',
            'online_sale_price_start_date',
            'online_sale_price_end_date',
            'online_business_price',
        );
    }

    //########################################

    public function clear($listingId = null)
    {
        $conditions = array();
        $listingId && $conditions['listing_id = ?'] = (int)$listingId;

        $this->getConnection()->delete($this->getMainTable(), $conditions);
    }

    public function build(\Ess\M2ePro\Model\Listing $listing)
    {
        if (!$listing->isComponentModeAmazon()) {
            throw new \Ess\M2ePro\Model\Exception\Logic("Wrong component provided [{$listing->getComponentMode()}]");
        }

        $select = $this->getBuildIndexSelect($listing);

        $createDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $createDate = $createDate->format('Y-m-d H:i:s');

        $select->columns(array(
            new \Zend_Db_Expr($this->getConnection()->quote($listing->getId())),
            new \Zend_Db_Expr($this->getConnection()->quote($createDate))
        ));

        $query = $this->getConnection()->insertFromSelect(
            $select,
            $this->getMainTable(),
            array(
                'listing_product_id',
                'min_regular_price',
                'max_regular_price',
                'min_business_price',
                'max_business_price',
                'listing_id',
                'create_date'
            ),
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
        );
        $this->getConnection()->query($query);
    }

    //########################################

    public function getBuildIndexSelect(\Ess\M2ePro\Model\Listing $listing)
    {
        $amazonListingProductTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
            ->getResource()->getMainTable();

        $listingProductTable = $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getMainTable();

        $select = $this->getConnection()->select()
            ->from(
                array('malp' => $amazonListingProductTable),
                array(
                    'variation_parent_id',
                    new \Zend_Db_Expr(
                        "MIN(
                            IF(
                                malp.online_regular_sale_price_start_date IS NOT NULL AND
                                malp.online_regular_sale_price_end_date IS NOT NULL AND
                                malp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                                malp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                                malp.online_regular_sale_price,
                                malp.online_regular_price
                            )
                        ) as variation_min_regular_price"
                    ),
                    new \Zend_Db_Expr(
                        "MAX(
                            IF(
                                malp.online_regular_sale_price_start_date IS NOT NULL AND
                                malp.online_regular_sale_price_end_date IS NOT NULL AND
                                malp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                                malp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                                malp.online_regular_sale_price,
                                malp.online_regular_price
                            )
                        ) as variation_max_regular_price"
                    ),
                    new \Zend_Db_Expr(
                        "MIN(
                            malp.online_business_price
                        ) as variation_min_business_price"
                    ),
                    new \Zend_Db_Expr(
                        "MAX(
                            malp.online_business_price
                        ) as variation_max_business_price"
                    )
                )
            )
            ->joinInner(
                array('mlp' => $listingProductTable),
                'malp.listing_product_id = mlp.id',
                array()
            )
            ->where('mlp.status IN (?)', array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN
            ))
            ->where('mlp.listing_id = ?', (int)$listing->getId())
            ->where('malp.variation_parent_id IS NOT NULL')
            ->group('malp.variation_parent_id');

        return $select;
    }

    //########################################
}