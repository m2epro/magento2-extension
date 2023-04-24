<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Delete extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->productTypeHelper = $productTypeHelper;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addErrorMessage($this->__('Please select Item(s) to remove.'));
            $this->_redirect('*/*/index');
            return;
        }

        $deleted = $locked = 0;
        foreach ($ids as $id) {
            $template = $this->productTypeHelper->getProductTypeById((int)$id);
            if ($template->isLocked() || $this->productTypeHelper->isProductTypeUsingInProducts($id)) {
                $locked++;
            } else {
                $template->delete();
                $deleted++;
            }
        }

        if ($deleted) {
            $tempString = $this->__('%deleted% record(s) were deleted.', $deleted);
            $this->messageManager->addSuccessMessage($tempString);
        }

        if ($locked) {
            $tempString  = $this->__('%locked% record(s) are used in Listing(s).', $locked) . ' ';
            $tempString .= $this->__('Product Type must not be in use to be deleted.');
            $this->messageManager->addErrorMessage($tempString);
        }

        $this->_redirect('*/*/index');
    }
}
