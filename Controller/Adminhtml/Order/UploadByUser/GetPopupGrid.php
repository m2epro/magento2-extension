<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order\UploadByUser;

class GetPopupGrid extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Order\UploadByUser\Grid $block */
        $block = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\UploadByUser\Grid::class);
        $block->setComponent($this->getRequest()->getParam('component'));
        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }
}
