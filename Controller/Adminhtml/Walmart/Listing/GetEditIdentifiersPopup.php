<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\GetEditIdentifiersPopup
 */
class GetEditIdentifiersPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Walmart_Listing_View_Walmart_Identifiers_Main')
        );

        return $this->getResult();
    }
}
