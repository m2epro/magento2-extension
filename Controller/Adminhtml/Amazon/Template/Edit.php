<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Edit
 */
class Edit extends Template
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $type = $this->getRequest()->getParam('type');

        if ($id === null || empty($type)) {
            $this->messageManager->addError($this->__('You should provide correct parameters.'));
            return $this->_redirect('*/*/index');
        }

        $type = $this->prepareTemplateType($type);

        return $this->_redirect(
            "*/amazon_template_{$type}/edit",
            ['id'=>$id]
        );
    }

    //########################################

    private function prepareTemplateType($type)
    {
        if ($type == \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SELLING_FORMAT) {
            return 'sellingFormat';
        }

        if ($type == \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_OVERRIDE) {
            return 'shippingOverride';
        }

        if ($type == \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_TEMPLATE) {
            return 'shippingTemplate';
        }

        if ($type == \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_PRODUCT_TAX_CODE) {
            return 'productTaxCode';
        }

        return $type;
    }

    //########################################
}
