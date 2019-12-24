<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat\Edit
 */
class Edit extends Template
{
    //########################################

    public function execute()
    {
        $template = $this->walmartFactory->getObject('Template\SellingFormat');
        if ($id = $this->getRequest()->getParam('id')) {
            $template->load($id);
        }

        if (!$template->getId() && $id) {
            $this->messageManager->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/walmart_template/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $template);

        $headerTextEdit = $this->__('Edit Selling Policy');
        $headerTextAdd = $this->__('Add Selling Policy');

        if ($template !== null
            && $template->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml($template->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Selling Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->setPageHelpLink('x/SgBhAQ');
        $this->addContent($this->createBlock('Walmart_Template_SellingFormat_Edit'));

        return $this->getResultPage();
    }

    //########################################
}
