<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Log;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id', false);
        if ($id) {
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);

            if (!$listing->getId()) {
                return;
            }
        }

        $response = $this->createBlock('Ebay\Listing\Log\Grid')->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}