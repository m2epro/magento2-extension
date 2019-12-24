<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add\SetAutoActionPopupShown
 */
class SetAutoActionPopupShown extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{
    public function execute()
    {
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/view/ebay/advanced/autoaction_popup/',
            'shown',
            1
        );

        return $this->getResult();
    }
}
