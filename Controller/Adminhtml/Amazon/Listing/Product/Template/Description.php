<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

abstract class Description extends Main
{
    //########################################

    protected function filterProductsForMapOrUnmapDescriptionTemplate($productsIdsParam)
    {
        $productsIds = [];
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {
            $connection = $this->resourceConnection->getConnection();
            $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
                ->getResource()->getMainTable();

            $select = $connection->select();

            // selecting all except parents general_id owners or simple general_id owners without general_id
            $select->from($tableAmazonListingProduct, 'listing_product_id')
                ->where('is_general_id_owner = 0
                OR (is_general_id_owner = 1
                    AND is_variation_parent = 0 AND general_id IS NOT NULL)');

            $select->where('listing_product_id IN (?)', $productsIdsParamChunk);

            $productsIds = array_merge($productsIds, $connection->fetchCol($select));
        }

        return $productsIds;
    }

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

    protected function setDescriptionTemplateFroProducts($productsIds, $templateId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
            ->getResource()->getMainTable();

        $productsIds = array_chunk($productsIds, 1000);
        foreach ($productsIds as $productsIdsChunk) {
            $connection->update($tableAmazonListingProduct,
                ['template_description_id' => $templateId],
                '`listing_product_id` IN ('.implode(',', $productsIdsChunk).')'
            );
        }
    }

    protected function runProcessorForParents($productsIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
            ->getResource()->getMainTable();

        $select = $connection->select();
        $select->from(array('alp' => $tableAmazonListingProduct), array('listing_product_id'))
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