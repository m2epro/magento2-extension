<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction\GetCategoryTemplatesList
 */
class GetCategoryTemplatesList extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', '');

        $collection = $this->activeRecordFactory->getObject('Walmart_Template_Category')->getCollection();

        $marketplaceId != '' && $collection->addFieldToFilter('marketplace_id', $marketplaceId);

        $this->setJsonContent($collection->getData());
        return $this->getResult();
    }

    //########################################
}
