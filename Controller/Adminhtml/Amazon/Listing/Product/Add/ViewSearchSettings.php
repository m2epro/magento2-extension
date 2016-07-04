<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class ViewSearchSettings extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');

        if (empty($listingId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $settings = $this->createBlock('Amazon\Listing\Create\Search');

        $this->setAjaxContent($settings->toHtml());
        
        return $this->getResult();
    }

    //########################################
}