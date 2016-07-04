<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class GetSpecificHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();
        $specifics = $this->getSpecificsFromPost($post);

        $categoryMode = $this->getRequest()->getParam('category_mode');
        $categoryValue = $this->getRequest()->getParam('category_value');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $uniqueId = $this->getRequest()->getParam('unique_id');

        $categoryBlock = $this->createBlock('Ebay\Listing\Product\Category\Settings\Specific');

        $categoryBlock->setMarketplaceId($marketplaceId);
        $categoryBlock->setCategoryMode($categoryMode);
        $categoryBlock->setCategoryValue($categoryValue);
        $categoryBlock->setUniqueId($uniqueId);
        $categoryBlock->setSelectedSpecifics($specifics);

        $this->setAjaxContent($categoryBlock->toHtml());

        return $this->getResult();
    }

    //########################################
}