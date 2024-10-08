<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Delete extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->templateProductTypeRepository = $templateProductTypeRepository;
    }

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addErrorMessage(__('Please select Item(s) to remove.'));
            $this->_redirect('*/*/index');

            return;
        }

        $deleted = $locked = 0;
        foreach ($ids as $id) {
            $template = $this->templateProductTypeRepository->get((int)$id);
            if (
                $template->isLocked()
                || $this->templateProductTypeRepository->isUsed($template)
            ) {
                $locked++;
            } else {
                $this->templateProductTypeRepository->remove($template);
                $deleted++;
            }
        }

        if ($deleted) {
            $tempString = __('%deleted record(s) were deleted.', ['deleted' => $deleted]);
            $this->messageManager->addSuccessMessage($tempString);
        }

        if ($locked) {
            $tempString = __('%locked record(s) are used in Listing(s).', ['locked' => $locked]) . ' ';
            $tempString .= __(
                'Unable to delete Product Type: It is currently in use in one or more Listings'
                . ' or Auto Rules. Please ensure the Product Type is not associated with any active records.'
            );
            $this->messageManager->addErrorMessage($tempString);
        }

        $this->_redirect('*/*/index');
    }
}
