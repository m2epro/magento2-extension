<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class AddFilterGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $motorsType = $this->getRequest()->getParam('motors_type');

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Filter\Grid $block */
        $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\Add\Filter\Grid');
        $block->setMotorsType($motorsType);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}