<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class Delete extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    private \Ess\M2ePro\Model\Walmart\ProductType\Service $productTypeService;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Service $productTypeService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->productTypeService = $productTypeService;
    }

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addErrorMessage((string)__('Please select Item(s) to remove.'));
            $this->_redirect('*/*/index');

            return;
        }

        $result = $this->productTypeService->deleteByIds($ids);

        if ($result->getCountDeleted() !== 0) {
            $this->messageManager->addSuccessMessage(
                (string)__('Product Type deleted.')
            );
        }

        if ($result->getCountLocked() !== 0) {
            $this->messageManager->addErrorMessage(
                (string)__(
                    'Unable to delete Product Type: It is currently in use in one or more Listings or Auto Rules.'
                    . ' Please ensure the Product Type is not associated with any active records.'
                )
            );
        }

        $this->_redirect('*/*/index');
    }
}
