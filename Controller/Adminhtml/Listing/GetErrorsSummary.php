<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

use Ess\M2ePro\Controller\Adminhtml\Listing;

class GetErrorsSummary extends Listing
{
    public function execute()
    {
        $blockParams = [
            'action_ids' => $this->getRequest()->getParam('action_ids'),
            'table_name' => $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getMainTable(),
            'type_log'   => 'listing'
        ];
        $block = $this->createBlock('Listing\Log\ErrorsSummary','', ['data' => $blockParams]);
        $this->setAjaxContent($block);

        return $this->getResult();
    }
}