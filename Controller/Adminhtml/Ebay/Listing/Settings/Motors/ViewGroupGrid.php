<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class ViewGroupGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $entityId = $this->getRequest()->getParam('entity_id');
        $motorsType = $this->getRequest()->getParam('motors_type');

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group\Grid $block */
        $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\View\Group\Grid');
        $block->setListingProductId($entityId);
        $block->setMotorsType($motorsType);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}