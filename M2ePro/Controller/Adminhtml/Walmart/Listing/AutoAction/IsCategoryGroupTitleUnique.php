<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction\IsCategoryGroupTitleUnique
 */
class IsCategoryGroupTitleUnique extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $groupId = $this->getRequest()->getParam('group_id');
        $title = $this->getRequest()->getParam('title');

        if ($title == '') {
            $this->setJsonContent(['unique' => false]);
            return $this->getResult();
        }

        $collection = $this->activeRecordFactory->getObject('Listing_Auto_Category_Group')
            ->getCollection()
            ->addFieldToFilter('listing_id', $listingId)
            ->addFieldToFilter('title', $title);

        if ($groupId) {
            $collection->addFieldToFilter('id', ['neq' => $groupId]);
        }

        $this->setJsonContent(['unique' => !(bool)$collection->getSize()]);
        return $this->getResult();
    }

    //########################################
}
