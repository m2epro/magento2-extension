<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetSelectedCategoryDetails
 */
class GetSelectedCategoryDetails extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $details = [
            'path'               => '',
            'interface_path'     => '',
            'template_id'        => null,
            'is_custom_template' => null
        ];

        $categoryHelper = $this->getHelper('Component_Ebay_Category');

        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId     = $this->getRequest()->getParam('account_id');
        $value         = $this->getRequest()->getParam('value');
        $mode          = $this->getRequest()->getParam('mode');
        $categoryType  = $this->getRequest()->getParam('category_type');

        switch ($mode) {
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                $details['path'] = $categoryHelper->isEbayCategoryType($categoryType)
                    ? $this->getHelper('Component_Ebay_Category_Ebay')->getPath($value, $marketplaceId)
                    : $this->getHelper('Component_Ebay_Category_Store')->getPath($value, $accountId);

                $details['interface_path'] = $details['path'] . ' (' . $value . ')';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                $details['path'] = $this->__('Magento Attribute') .' > '.
                    $this->getHelper('Magento\Attribute')->getAttributeLabel($value);

                $details['interface_path'] = $details['path'];

                break;
        }

        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
            $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            $template->loadByCategoryValue($value, $mode, $marketplaceId, 0);

            $details['is_custom_template'] = $template->getIsCustomTemplate();
            $details['template_id']        = $template->getId();
        }

        $this->setJsonContent($details);

        return $this->getResult();
    }

    //########################################
}
