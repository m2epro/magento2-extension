<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

class IsCategoryGroupTitleUnique extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $groupId = $this->getRequest()->getParam('group_id');
        $title = $this->getRequest()->getParam('title');

        if ($title == '') {
            $this->setJsonContent(array('unique' => false));
            return $this->getResult();
        }

        $collection = $this->activeRecordFactory->getObject('Listing\Auto\Category\Group')
            ->getCollection()
            ->addFieldToFilter('listing_id', $listingId)
            ->addFieldToFilter('title', $title);

        if ($groupId) {
            $collection->addFieldToFilter('id', array('neq' => $groupId));
        }

        $this->setJsonContent(array('unique' => !(bool)$collection->getSize()));
        return $this->getResult();
    }

    //########################################
}