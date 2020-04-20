<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation
 */
class Congratulation extends Installation
{
    protected function _construct()
    {
        parent::_construct();

        $this->updateButton('continue', 'label', $this->__('Complete'));
        $this->updateButton('continue', 'class', 'primary');
    }

    protected function getStep()
    {
        return 'congratulation';
    }

    protected function _beforeToHtml()
    {
        $referrer = $this->getRequest()->getParam('referrer');

        if ($referrer == \Ess\M2ePro\Helper\View\Amazon::NICK) {
            $this->jsUrl->add($this->getUrl('*/amazon_listing/index'), 'complete');
        } elseif ($referrer == \Ess\M2ePro\Helper\View\Walmart::NICK) {
            $this->jsUrl->add($this->getUrl('*/walmart_listing/index'), 'complete');
        } else {
            $this->jsUrl->add($this->getUrl('*/ebay_listing/index'), 'complete');
        }

        return parent::_beforeToHtml();
    }
}
