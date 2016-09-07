<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

abstract class ShippingOverride extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
{
    //########################################

    protected function setShippingOverrideTemplateForProducts($productsIds, $templateId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
            ->getResource()->getMainTable();

        return $connection->update(
            $tableAmazonListingProduct,
            [ 'template_shipping_override_id' => $templateId ],
            '`listing_product_id` IN ('.implode(',', $productsIds).')'
        );
    }

    //########################################
}