<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingOverride;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingOverride\Edit
 */
class Edit extends Template
{
    public function execute()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon_Template_ShippingOverride', $id, null, false);

        if ($model === null && $id) {
            $this->getMessageManager()->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/amazon_template/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $model);

        $headerTextEdit = $this->__("Edit Shipping Override Policy");
        $headerTextAdd = $this->__("Add Shipping Override Policy");

        if ($model !== null
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

        $this->addContent($this->createBlock('Amazon_Template_ShippingOverride_Edit'));

        $this->setPageHelpLink('x/jAA0AQ');

        return $this->getResultPage();
    }
}
