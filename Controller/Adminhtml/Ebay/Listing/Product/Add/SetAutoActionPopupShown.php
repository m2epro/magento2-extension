<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

class SetAutoActionPopupShown extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{
    public function execute() {
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/view/ebay/advanced/autoaction_popup/', 'shown', 1
        );

        return $this->getResult();
    }
}