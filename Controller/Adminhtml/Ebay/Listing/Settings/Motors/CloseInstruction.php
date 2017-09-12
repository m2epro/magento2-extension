<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class CloseInstruction extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module')->getCacheConfig()->setGroupValue('/ebay/motors/','was_instruction_shown', 1);
    }

    //########################################
}