<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class ViewItem extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $entityId = $this->getRequest()->getParam('entity_id');
        $motorsType = $this->getRequest()->getParam('motors_type');

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Item $block */
        $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\View\Item');
        $block->setListingProductId($entityId);
        $block->setMotorsType($motorsType);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}