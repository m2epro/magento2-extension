<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Log;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id', false);
        if ($id) {
            $listing = $this->activeRecordFactory->getObjectLoaded('Listing', $id, 'id', false);

            if (is_null($listing)) {
                $listing = $this->activeRecordFactory->getObject('Listing');
            }

            if (is_null($listing)) {
                $listing = $this->activeRecordFactory->getObject('Listing');
            }

            if (!$listing->getId()) {
                return;
            }
        }

        $block = $this->createBlock('Amazon\Listing\Log\Grid');
        $this->setAjaxContent($block->toHtml());

        return $this->getResult();
    }

    //########################################
}