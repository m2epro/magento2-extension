<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order\UploadByUser;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\UploadByUser\GetPopupGrid
 */
class GetPopupGrid extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Order\UploadByUser\Grid $block */
        $block = $this->createBlock('Order_UploadByUser_Grid');
        $block->setComponent($this->getRequest()->getParam('component'));

        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }

    //########################################
}
