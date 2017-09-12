<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class GetGroupContentView extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $groupId = $this->getRequest()->getParam('group_id');

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Group', $groupId);

        if ($model->isModeItem()) {
            $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\View\Group\Items');
        } else {
            $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\View\Group\Filters');
        }

        $block->setGroupId($groupId);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}