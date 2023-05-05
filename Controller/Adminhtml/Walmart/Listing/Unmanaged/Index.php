<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Unmanaged;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Unmanaged
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Unmanaged\Grid::class)
            );

            return $this->getResult();
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Unmanaged::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend(__('All Unmanaged Items'));

        $this->setPageHelpLink('x/ev1IB');

        return $this->getResult();
    }
}
