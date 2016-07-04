<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class Edit extends Description
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $templateModel */
        $id = $this->getRequest()->getParam('id');
        $templateModel = $this->amazonFactory->getObject('Template\Description');

        if ($id) {
            $templateModel->load($id);
        }

        $marketplaces = $this->getHelper('Component\Amazon')->getMarketplacesAvailableForAsinCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Amazon Marketplace.';
            $this->messageManager->addError($this->__($message));
            return $this->_redirect('*/*/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $templateModel);

        $this->addContent($this->createBlock('Amazon\Template\Description\Edit'));

        if ($templateModel->getId()) {
            $headerText = $this->__("Edit Description Policy");
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml(
                                $templateModel->getTitle()).'"';
        } else {
            $headerText = $this->__("Add Description Policy");
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);
        $this->setComponentPageHelpLink('Description');

        return $this->getResultPage();
    }

    //########################################
}