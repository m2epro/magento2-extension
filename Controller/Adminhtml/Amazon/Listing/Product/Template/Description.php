<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

abstract class Description extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
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

    //########################################
}