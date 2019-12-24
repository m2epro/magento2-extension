<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Synchronization\Edit
 */
class Edit extends Template
{
    public function execute()
    {
        $template = null;
        if ($id = $this->getRequest()->getParam('id')) {
            $template = $this->amazonFactory->getObjectLoaded('Template\Synchronization', $id);
        }

        if ($template === null && $id) {
            $this->messageManager->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/amazon_template/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $template);

        $headerTextEdit = $this->__("Edit Synchronization Policy");
        $headerTextAdd = $this->__("Add Synchronization Policy");

        if ($template !== null
            && $template->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml($template->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Synchronization Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->setPageHelpLink('x/EgItAQ');
        $this->addContent($this->createBlock('Amazon_Template_Synchronization_Edit'));

        return $this->getResultPage();
    }
}
