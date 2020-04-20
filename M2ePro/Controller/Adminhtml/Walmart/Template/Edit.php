<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Edit
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
            "*/walmart_template_{$type}/edit",
            ['id'=>$id]
        );
    }

    //########################################

    private function prepareTemplateType($type)
    {
        if ($type == \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_SELLING_FORMAT) {
            return 'sellingFormat';
        }

        return $type;
    }

    //########################################
}
