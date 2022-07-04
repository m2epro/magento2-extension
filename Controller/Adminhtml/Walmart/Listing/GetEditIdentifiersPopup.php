<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class GetEditIdentifiersPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Walmart\Identifiers\Main::class)
        );

        return $this->getResult();
    }
}
