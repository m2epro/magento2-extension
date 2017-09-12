<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class AddItemGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $motorsType = $this->getRequest()->getParam('motors_type');
        $identifierType = $this->getHelper('Component\Ebay\Motors')->getIdentifierKey($motorsType);

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Grid $block */
        $block = $this->createBlock(
            'Ebay\Listing\View\Settings\Motors\Add\Item\\' . ucfirst($identifierType) . '\Grid'
        );
        $block->setMotorsType($motorsType);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}