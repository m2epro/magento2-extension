<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

class Edit extends Category
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->globalData = $globalData;
        $this->dataHelper = $dataHelper;
        $this->walmartHelper = $walmartHelper;
    }

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Template\Category $templateModel */
        $id = $this->getRequest()->getParam('id');
        $templateModel = $this->activeRecordFactory->getObject('Walmart_Template_Category');

        if ($id) {
            $templateModel->load($id);
        }

        $marketplaces = $this->walmartHelper->getMarketplacesAvailableForApiCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Walmart Marketplace.';
            $this->messageManager->addError($this->__($message));
            return $this->_redirect('*/*/index');
        }

        $this->globalData->setValue('tmp_template', $templateModel);

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Edit::class)
        );

        if ($templateModel->getId()) {
            $headerText = $this->__("Edit Category Policy");
            $headerText .= ' "'.$this->dataHelper->escapeHtml(
                $templateModel->getTitle()
            ).'"';
        } else {
            $headerText = $this->__("Add Category Policy");
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);
        $this->setPageHelpLink('x/bf1IB');

        return $this->getResultPage();
    }
}
