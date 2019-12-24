<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetPath
 */
class GetPath extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $value = $this->getRequest()->getParam('value');
        $mode = $this->getRequest()->getParam('mode');
        $categoryType = $this->getRequest()->getParam('category_type');

        $ebayCategoryTypes = $this->getHelper('Component_Ebay_Category')->getEbayCategoryTypes();
        $storeCategoryTypes = $this->getHelper('Component_Ebay_Category')->getStoreCategoryTypes();

        if ($value === null || $mode === null
            || (in_array($categoryType, $ebayCategoryTypes) && $marketplaceId === null)
            || (in_array($categoryType, $storeCategoryTypes) && $accountId === null)
        ) {
            $this->getResponse()->setBody('');
            return;
        }

        $path = '';

        switch ($mode) {
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                if (in_array($categoryType, $ebayCategoryTypes)) {
                    $path = $this->getHelper('Component_Ebay_Category_Ebay')->getPath($value, $marketplaceId);
                } else {
                    $path = $this->getHelper('Component_Ebay_Category_Store')->getPath($value, $accountId);
                }

                $path .= ' (' . $value . ')';

                break;
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                $attributeLabel = $this->getHelper('Magento\Attribute')->getAttributeLabel($value);
                $path = $this->__('Magento Attribute') . ' > ' . $attributeLabel;

                break;
        }

        $this->setAjaxContent($path, false);

        return $this->getResult();
    }

    //########################################
}
