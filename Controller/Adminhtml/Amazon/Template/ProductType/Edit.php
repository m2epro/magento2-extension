<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Edit extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonComponentHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Component\Amazon $amazonComponentHelper
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonComponentHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->dataHelper = $dataHelper;
        $this->amazonComponentHelper = $amazonComponentHelper;
        $this->productTypeFactory = $productTypeFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $productType */
        $productType = $this->productTypeFactory->create();

        if ($id) {
            $productType->load($id);
        }

        $marketplaces = $this->amazonComponentHelper->getMarketplacesAvailableForAsinCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Amazon Marketplace.';
            $this->messageManager->addErrorMessage($this->__($message));
            return $this->_redirect('*/*/index');
        }

        $this->addContent(
            $this->getLayout()
                ->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit::class,
                    '',
                    ['productType' => $productType]
                )
        );

        if ($productType->getId()) {
            $headerText = $this->__("Edit Product Type Settings");
            $headerText .= ' "' . $this->dataHelper->escapeHtml(
                $productType->getTitle()
            ) . '"';
        } else {
            $headerText = $this->__("Add Product Type Settings");
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);
        $this->setPageHelpLink('x/OP8UB'); // TODO writing

        return $this->getResultPage();
    }
}
