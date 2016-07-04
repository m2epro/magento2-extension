<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Edit extends Template
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $type = $this->getRequest()->getParam('type');

        if (is_null($id) || empty($type)) {
            $this->messageManager->addError($this->__('You should provide correct parameters.'));
            return $this->_redirect('*/*/index');
        }

        $type = $this->prepareTemplateType($type);

        return $this->_redirect(
            "*/amazon_template_{$type}/edit", array('id'=>$id)
        );
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