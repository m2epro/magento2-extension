<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Indexer\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Indexer\Listing\Product\VariationParent
 */
class VariationParent extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_indexer_listing_product_variation_parent', 'listing_product_id');
    }

    //########################################

    public function getTrackedFields()
    {
        return [
            'online_price',
        ];
    }

    //########################################

    public function clear($listingId = null)
    {
        $conditions = [];
        $listingId && $conditions['listing_id = ?'] = (int)$listingId;

        $this->getConnection()->delete($this->getMainTable(), $conditions);
    }

    public function build(\Ess\M2ePro\Model\Listing $listing)
    {
        if (!$listing->isComponentModeWalmart()) {
            throw new \Ess\M2ePro\Model\Exception\Logic("Wrong component provided [{$listing->getComponentMode()}]");
        }

        $select = $this->getBuildIndexSelect($listing);

        $createDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $createDate = $createDate->format('Y-m-d H:i:s');

        $select->columns([
            new \Zend_Db_Expr($this->getConnection()->quote($listing->getId())),
            new \Zend_Db_Expr($this->getConnection()->quote($createDate))
        ]);

        $query = $this->getConnection()->insertFromSelect(
            $select,
            $this->getMainTable(),
            [
                'listing_product_id',
                'min_price',
                'max_price',
                'listing_id',
                'create_date'
            ],
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
        );
        $this->getConnection()->query($query);
    }

    //########################################

    public function getBuildIndexSelect(\Ess\M2ePro\Model\Listing $listing)
    {
        $listingProductTable = $this->activeRecordFactory
            ->getObject('Listing\Product')->getResource()->getMainTable();
        $walmartListingProductTable = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product')->getResource()->getMainTable();

        $select = $this->getConnection()->select()
            ->from(
                [
                    'mwlp' => $walmartListingProductTable
                ],
                [
                    'variation_parent_id',
                    new \Zend_Db_Expr(
                        "MIN(mwlp.online_price) as variation_min_price"
                    ),
                    new \Zend_Db_Expr(
                        "MAX(mwlp.online_price) as variation_max_price"
                    ),
                ]
            )
            ->joinInner(
                [
                    'mlp' => $listingProductTable
                ],
                'mwlp.listing_product_id = mlp.id',
                []
            )
            ->where('mlp.status IN (?)', [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
            ])
            ->where('mlp.listing_id = ?', (int)$listing->getId())
            ->where('mwlp.variation_parent_id IS NOT NULL')
            ->group('mwlp.variation_parent_id');

        return $select;
    }

    //########################################
}
