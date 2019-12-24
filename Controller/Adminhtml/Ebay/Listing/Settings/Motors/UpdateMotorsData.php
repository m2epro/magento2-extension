<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\UpdateMotorsData
 */
class UpdateMotorsData extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('listing_id');
        $listingProductIds = $this->getRequest()->getParam('listing_products_ids');
        $motorsType = $this->getRequest()->getParam('motors_type');
        $overwrite = $this->getRequest()->getParam('overwrite', 0) == 1;

        $items = $this->getRequest()->getParam('items');
        $filtersIds = $this->getRequest()->getParam('filters_ids');
        $groupsIds = $this->getRequest()->getParam('groups_ids');

        if (!is_array($listingProductIds)) {
            $listingProductIds = explode(',', $listingProductIds);
        }

        parse_str($items, $items);
        $itemsData = [];
        foreach ($items as $id => $note) {
            $itemsData[] = [
                'id' => $id,
                'note' => $note
            ];
        }

        if (!empty($filtersIds) && !is_array($filtersIds)) {
            $filtersIds = explode(',', $filtersIds);
        }

        if (!empty($groupsIds) && !is_array($groupsIds)) {
            $groupsIds = explode(',', $groupsIds);
        }

        $attrValue = $this->getHelper('Component_Ebay_Motors')->buildAttributeValue([
            'items' => $itemsData,
            'filters' => $filtersIds,
            'groups' => $groupsIds
        ]);

        $motorsAttribute = $this->getHelper('Component_Ebay_Motors')->getAttribute($motorsType);

        $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->updateMotorsAttributesData(
            $listingId,
            $listingProductIds,
            $motorsAttribute,
            $attrValue,
            $overwrite
        );

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}
