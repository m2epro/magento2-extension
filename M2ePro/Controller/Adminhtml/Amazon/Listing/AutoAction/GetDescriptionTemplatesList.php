<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction\GetDescriptionTemplatesList
 */
class GetDescriptionTemplatesList extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', '');
        $isNewAsinAccepted = $this->getRequest()->getParam('is_new_asin_accepted', 0);

        $collection = $this->amazonFactory->getObject('Template\Description')->getCollection();

        $marketplaceId != '' && $collection->addFieldToFilter('marketplace_id', $marketplaceId);

        $descriptionTemplates = $collection->getData();
        if ($isNewAsinAccepted == 1) {
            usort($descriptionTemplates, function ($a, $b) {
                return $a["is_new_asin_accepted"] < $b["is_new_asin_accepted"];
            });
        }

        $this->setJsonContent($descriptionTemplates);
        return $this->getResult();
    }

    //########################################
}
