<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Indexer\Listing\Product;

class VariationParent extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_indexer_listing_product_variation_parent', 'listing_product_id');
    }

    //########################################

    public function getTrackedFields()
    {
        return array(
            'online_current_price',
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
        if (!$listing->isComponentModeEbay()) {
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
                'min_price',
                'max_price',
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
        $listingProductVariationTable = $this->activeRecordFactory->getObject('Listing\Product\Variation')
            ->getResource()->getMainTable();

        $ebayListingProductVariationTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product\Variation')
            ->getResource()->getMainTable();

        $listingProductTable = $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getMainTable();

        $select = $this->getConnection()->select()
            ->from(
                array('mlpv' => $listingProductVariationTable),
                array(
                    'listing_product_id'
                )
            )
            ->joinInner(
                array('melpv' => $ebayListingProductVariationTable),
                'mlpv.id = melpv.listing_product_variation_id',
                array(
                    new \Zend_Db_Expr('MIN(`melpv`.`online_price`) as variation_min_price'),
                    new \Zend_Db_Expr('MAX(`melpv`.`online_price`) as variation_max_price')
                )
            )
            ->joinInner(
                array('mlp' => $listingProductTable),
                'mlpv.listing_product_id = mlp.id',
                array()
            )
            ->where('mlp.listing_id = ?', (int)$listing->getId())
            ->where('melpv.status != ?', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)
            ->group('mlpv.listing_product_id');

        return $select;
    }

    //########################################
}