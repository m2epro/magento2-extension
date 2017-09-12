<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class RemoveFilterFromProduct extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $filtersIds = $this->getRequest()->getParam('filters_ids');
        $entityId = $this->getRequest()->getParam('entity_id');
        $motorsType = $this->getRequest()->getParam('motors_type');

        if (!is_array($filtersIds)) {
            $filtersIds = explode(',', $filtersIds);
        }

        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $entityId);

        $motorsAttribute = $this->getHelper('Component\Ebay\Motors')->getAttribute($motorsType);
        $attributeValue = $listingProduct->getMagentoProduct()->getAttributeValue($motorsAttribute);

        $motorsData = $this->getHelper('Component\Ebay\Motors')->parseAttributeValue($attributeValue);

        foreach ($filtersIds as $filterId) {
            if (($key = array_search($filterId, $motorsData['filters'])) !== false) {
                unset($motorsData['filters'][$key]);
            }
        }

        $attributeValue = $this->getHelper('Component\Ebay\Motors')->buildAttributeValue($motorsData);

        $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->updateMotorsAttributesData(
            $listingProduct->getListingId(), [$entityId], $motorsAttribute, $attributeValue, true
        );

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}