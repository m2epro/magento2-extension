<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetSpecificHtml
 */
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

        $categoryBlock = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Specific::class);

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
