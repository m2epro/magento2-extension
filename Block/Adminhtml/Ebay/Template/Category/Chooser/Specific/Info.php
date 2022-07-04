<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific;

class Info extends \Ess\M2ePro\Block\Adminhtml\Widget\Info
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    private $magentoAttributeHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Magento\Framework\Math\Random $random,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($random, $context, $data);

        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
    }
    protected function _prepareLayout()
    {
        if ($this->getData('category_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $category = $this->__('Magento Attribute') .' > '.
                $this->magentoAttributeHelper->getAttributeLabel($this->getData('category_value'));

        } else {
            $category = $this->componentEbayCategoryEbay->getPath(
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
