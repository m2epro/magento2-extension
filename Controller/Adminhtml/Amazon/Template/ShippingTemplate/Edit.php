<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingTemplate;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Edit extends Template
{
    public function execute()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon\Template\ShippingTemplate', $id, NULL, false);

        if (is_null($model) && $id) {
            $this->getMessageManager()->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/amazon_template/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $model);

        $headerTextEdit = $this->__('Edit Shipping Template Policy');
        $headerTextAdd = $this->__('Add Shipping Template Policy');

        if (!is_null($model)
            && $model->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml($model->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Shipping Template Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->addContent($this->createBlock('Amazon\Template\ShippingTemplate\Edit'));

        $this->setPageHelpLink('x/wwA9AQ');

        return $this->getResultPage();
    }
}