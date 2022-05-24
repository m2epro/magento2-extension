<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;

class GetRecent extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->componentEbayCategory = $componentEbayCategory;
    }

    public function execute()
    {
        $categoryType = $this->getRequest()->getParam('category_type');
        $selectedCategory = $this->getRequest()->getParam('selected_category');

        if (in_array($categoryType, $this->componentEbayCategory->getEbayCategoryTypes())) {
            $categories = $this->componentEbayCategory->getRecent(
                $this->getRequest()->getParam('marketplace'),
                $categoryType,
                $selectedCategory
            );
        } else {
            $categories = $this->componentEbayCategory->getRecent(
                $this->getRequest()->getParam('account'),
                $categoryType,
                $selectedCategory
            );
        }

        $this->setJsonContent($categories);

        return $this->getResult();
    }

    //########################################
}
