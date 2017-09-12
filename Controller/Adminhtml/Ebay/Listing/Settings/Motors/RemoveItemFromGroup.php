<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class RemoveItemFromGroup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $itemsIds = $this->getRequest()->getParam('items_ids');
        $groupId = $this->getRequest()->getParam('group_id');

        if (!is_array($itemsIds)) {
            $itemsIds = explode(',', $itemsIds);
        }

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Group', $groupId);
        $items = $model->getItems();

        foreach ($itemsIds as $itemId) {
            unset($items[$itemId]);
        }

        if (count($items) > 0) {
            $model->setItemsData($this->getHelper('Component\Ebay\Motors')->buildItemsAttributeValue($items));
            $model->save();
        } else {
            $model->delete();
        }

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}