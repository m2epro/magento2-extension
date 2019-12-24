<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplateAssignByMagentoCategory
 */
class CategoryTemplateAssignByMagentoCategory extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        if (empty($listingProductsIds)) {
            $this->_forward('index');
            return;
        }

        $listing = $this->getListing();

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_add', $listing);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->createBlock('Walmart_Listing_Product_Add_CategoryTemplate_Category_Grid');
            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        $this->setPageHelpLink('x/L4taAQ');
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Set Category Policy')
        );

        $this->addContent($this->createBlock('Walmart_Listing_Product_Add_CategoryTemplate_Category'));

        return $this->getResult();
    }

    //########################################
}
