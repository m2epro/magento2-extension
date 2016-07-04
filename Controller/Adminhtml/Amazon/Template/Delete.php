<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Delete extends Template
{
    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();
        $type = $this->getRequest()->getParam('type');

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Item(s) to remove.'));
            return $this->_redirect('*/*/index');
        }

        if (empty($type)) {
            $this->messageManager->addError($this->__('You should provide correct parameters.'));
            return $this->_redirect('*/*/index');
        }

        $type = $this->prepareTemplateType($type);

        $deleted = $locked = 0;

        foreach ($ids as $id) {
            if (strtolower($type) == \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_OVERRIDE) {
                $template = $this->activeRecordFactory->getObject('Amazon\Template\ShippingOverride')->load($id);
            } else {
                $template = $this->amazonFactory->getObjectLoaded('Template\\' . $type, $id);
            }

            if ($template->isLocked()) {
                $locked++;
            } else {
                $template->delete();
                $deleted++;
            }
        }

        $tempString = $this->__('%amount% record(s) were successfully deleted.', $deleted);
        $deleted && $this->messageManager->addSuccess($tempString);

        $tempString  = $this->__('%amount% record(s) are used in Listing(s).', $locked) . ' ';
        $tempString .= $this->__('Policy must not be in use to be deleted.');
        $locked && $this->messageManager->addError($tempString);

        $this->_redirect('*/*/index');
    }

    //########################################

    private function prepareTemplateType($type)
    {
        if ($type == \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SELLING_FORMAT) {
            return 'SellingFormat';
        }

        if ($type == \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_OVERRIDE) {
            return 'shippingOverride';
        }

        return ucfirst($type);
    }

    //########################################
}