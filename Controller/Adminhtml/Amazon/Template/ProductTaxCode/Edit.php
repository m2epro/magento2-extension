<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Edit extends Template
{
    public function execute()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon\Template\ProductTaxCode', $id, NULL, false);

        if (is_null($model) && $id) {
            $this->getMessageManager()->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/amazon_template/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $model);

        $headerTextEdit = $this->__('Edit Product Tax Code Policies');
        $headerTextAdd = $this->__('Add Product Tax Code Policies');

        if (!is_null($model)
            && $model->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml($model->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Product Tax Code Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->addContent($this->createBlock('Amazon\Template\ProductTaxCode\Edit'));

        // todo must be added when develop branch becomes "public"
        $this->setPageHelpLink('TODO LINK');

        return $this->getResultPage();
    }
}