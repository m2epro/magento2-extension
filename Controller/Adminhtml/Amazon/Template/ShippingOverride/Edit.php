<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingOverride;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Edit extends Template
{
    public function execute()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon\Template\ShippingOverride', $id, NULL, false);

        if (is_null($model) && $id) {
            $this->getMessageManager()->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/amazon_template/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $model);

        $headerTextEdit = $this->__("Edit Shipping Override Policy");
        $headerTextAdd = $this->__("Add Shipping Override Policy");

        if (!is_null($model)
            && $model->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml($model->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Shipping Override Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->addContent($this->createBlock('Amazon\Template\ShippingOverride\Edit'));

        $this->setPageHelpLink('x/jAA0AQ');

        return $this->getResultPage();
    }
}