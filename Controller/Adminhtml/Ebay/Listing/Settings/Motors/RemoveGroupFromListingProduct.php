<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class RemoveGroupFromListingProduct extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $groupsIds = $this->getRequest()->getParam('groups_ids');
        $entityId = $this->getRequest()->getParam('entity_id');
        $motorsType = $this->getRequest()->getParam('motors_type');

        if (!is_array($groupsIds)) {
            $groupsIds = explode(',', $groupsIds);
        }

        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $entityId);

        $motorsAttribute = $this->getHelper('Component\Ebay\Motors')->getAttribute($motorsType);
        $attributeValue = $listingProduct->getMagentoProduct()->getAttributeValue($motorsAttribute);

        $motorsData = $this->getHelper('Component\Ebay\Motors')->parseAttributeValue($attributeValue);

        foreach ($groupsIds as $filterId) {
            if (($key = array_search($filterId, $motorsData['groups'])) !== false) {
                unset($motorsData['groups'][$key]);
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