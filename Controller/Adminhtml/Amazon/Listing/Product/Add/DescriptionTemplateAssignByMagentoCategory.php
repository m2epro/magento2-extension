<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\DescriptionTemplateAssignByMagentoCategory
 */
class DescriptionTemplateAssignByMagentoCategory extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingProductsIds = $this->getListing()
            ->getSetting('additional_data', 'adding_new_asin_listing_products_ids');

        if (empty($listingProductsIds)) {
            $this->_forward('index');
            return;
        }

        $listing = $this->getListing();

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_add', $listing);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->getLayout()
                    ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin\Category\Grid::class);
            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        $this->setPageHelpLink('x/cwQVB');
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Set Description Policy for New ASIN/ISBN Creation')
        );

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin\Category::class)
        );

        return $this->getResult();
    }

    //########################################
}
