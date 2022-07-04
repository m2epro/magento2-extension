<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping\Edit
 */
class Edit extends Template
{
    public function execute()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon_Template_Shipping', $id, null, false);

        if ($model === null && $id) {
            $this->getMessageManager()->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/amazon_template/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $model);

        $headerTextEdit = $this->__('Edit Shipping Policy');
        $headerTextAdd = $this->__('Add Shipping Policy');

        if ($model !== null
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

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Template\Shipping\Edit::class)
        );

        $this->setPageHelpLink('x/6-0kB');

        return $this->getResultPage();
    }
}
