<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\Edit
 */
class Edit extends Category
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Template\Category $templateModel */
        $id = $this->getRequest()->getParam('id');
        $templateModel = $this->activeRecordFactory->getObject('Walmart_Template_Category');

        if ($id) {
            $templateModel->load($id);
        }

        $marketplaces = $this->getHelper('Component\Walmart')->getMarketplacesAvailableForApiCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Walmart Marketplace.';
            $this->messageManager->addError($this->__($message));
            return $this->_redirect('*/*/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('tmp_template', $templateModel);

        $this->addContent($this->createBlock('Walmart_Template_Category_Edit'));

        if ($templateModel->getId()) {
            $headerText = $this->__("Edit Category Policy");
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml(
                $templateModel->getTitle()
            ).'"';
        } else {
            $headerText = $this->__("Add Category Policy");
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);
        $this->setPageHelpLink('x/RQBhAQ');

        return $this->getResultPage();
    }

    //########################################
}
