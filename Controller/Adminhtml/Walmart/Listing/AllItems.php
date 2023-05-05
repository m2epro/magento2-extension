<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class AllItems extends Main
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings');
    }

    public function execute()
    {
        if ($this->isAjax()) {
            $gridBlock = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AllItems\Grid::class;
            $this->setAjaxContent(
                $this->getLayout()->createBlock($gridBlock)
            );

            return $this->getResult();
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AllItems::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('All Items'));

        return $this->getResult();
    }
}
