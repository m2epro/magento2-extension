<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

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

        $ebayCategoryTypes = $this->getHelper('Component\Ebay\Category')->getEbayCategoryTypes();
        $storeCategoryTypes = $this->getHelper('Component\Ebay\Category')->getStoreCategoryTypes();

        if (is_null($value) || is_null($mode)
            || (in_array($categoryType, $ebayCategoryTypes) && is_null($marketplaceId))
            || (in_array($categoryType, $storeCategoryTypes) && is_null($accountId))
        ) {
            $this->getResponse()->setBody('');
            return;
        }

        $path = '';

        switch ($mode) {
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                if (in_array($categoryType, $ebayCategoryTypes)) {
                    $path = $this->getHelper('Component\Ebay\Category\Ebay')->getPath($value, $marketplaceId);
                } else {
                    $path = $this->getHelper('Component\Ebay\Category\Store')->getPath($value, $accountId);
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