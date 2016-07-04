<?php
/**
 * Created by PhpStorm.
 * User: myown
 * Date: 11.03.16
 * Time: 13:26
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

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