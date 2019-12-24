<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
 */
abstract class Template extends Main
{
    //########################################

    protected function filterLockedProducts($productsIdsParam)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable();

        $productsIds = [];
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {
            $select = $connection->select();
            $select->from(['lo' => $table], ['object_id'])
                ->where('model_name = "Listing\Product"')
                ->where('object_id IN (?)', $productsIdsParamChunk)
                ->where('tag IS NOT NULL');

            $lockedProducts = $connection->fetchCol($select);

            foreach ($lockedProducts as $id) {
                $key = array_search($id, $productsIdsParamChunk);
                if ($key !== false) {
                    unset($productsIdsParamChunk[$key]);
                }
            }

            $productsIds = array_merge($productsIds, $productsIdsParamChunk);
        }

        return $productsIds;
    }

    //########################################

    protected function runProcessorForParents($productsIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon_Listing_Product')
            ->getResource()->getMainTable();

        $select = $connection->select();
        $select->from(['alp' => $tableAmazonListingProduct], ['listing_product_id'])
            ->where('listing_product_id IN (?)', $productsIds)
            ->where('is_variation_parent = ?', 1);

        $productsIds = $connection->fetchCol($select);

        foreach ($productsIds as $productId) {
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);
            $listingProduct->getChildObject()->getVariationManager()->getTypeModel()->getProcessor()->process();
        }
    }

    //########################################
}
