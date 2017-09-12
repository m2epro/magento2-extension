<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class GetSaveAsGroupForm extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group\Form $block */
        $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\Add\Group\Form');

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}