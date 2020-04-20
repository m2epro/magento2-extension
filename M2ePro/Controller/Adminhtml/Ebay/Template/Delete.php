<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Delete
 */
class Delete extends Template
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $id = $this->getRequest()->getParam('id');
        $nick = $this->getRequest()->getParam('nick');
        // ---------------------------------------

        // ---------------------------------------
        $manager = $this->templateManager->setTemplate($nick);
        $template = $manager
            ->getTemplateModel()
            ->getCollection()
            ->addFieldToFilter('id', $id)
            ->addFieldToFilter('is_custom_template', 0)
            ->getFirstItem();
        // ---------------------------------------

        if (!$template->getId()) {
            $this->messageManager->addError($this->__('Policy does not exist.'));
            $this->_redirect('*/*/index');
            return;
        }

        if (!$template->isLocked()) {
            $template->delete();

            $this->messageManager->addSuccess(
                $this->__('Policy was successfully deleted.')
            );
        } else {
            $this->messageManager->addError(
                $this->__('Policy cannot be deleted as it is used in Listing Settings.')
            );
        }

        $this->_redirect('*/*/index');
    }

    //########################################
}
