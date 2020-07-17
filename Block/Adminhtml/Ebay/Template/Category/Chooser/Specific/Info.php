<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Info
 */
class Info extends \Ess\M2ePro\Block\Adminhtml\Widget\Info
{
    //########################################

    protected function _prepareLayout()
    {
        if ($this->getData('category_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $category = $this->__('Magento Attribute') .' > '.
                $this->getHelper('Magento\Attribute')->getAttributeLabel($this->getData('category_value'));

        } else {
            $category = $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                $this->getData('category_value'),
                $this->getData('marketplace_id')
            );
            $category .= ' (' . $this->getData('category_value') . ')';
        }

        $this->setInfo(
            [
                [
                    'label' => $this->__('Category'),
                    'value' => $category
                ]
            ]
        );

        return parent::_prepareLayout();
    }

    //########################################
}
