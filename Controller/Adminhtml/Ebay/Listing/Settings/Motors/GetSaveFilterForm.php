<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class GetSaveFilterForm extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Filter\Form $block */
        $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\Add\Filter\Form');

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}