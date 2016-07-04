<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class DescriptionTemplateAssignByMagentoCategory extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data','adding_new_asin_listing_products_ids');

        if (empty($listingProductsIds)) {
            $this->_forward('index');
            return;
        }

        $listing = $this->getListing();

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_add', $listing);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->createBlock('Amazon\Listing\Product\Add\NewAsin\Category\Grid');
            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        $this->setPageHelpLink(NULL, 'pages/viewpage.action?pageId=18188493');
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Set Description Policy for New ASIN/ISBN Creation')
        );

        $this->addContent($this->createBlock('Amazon\Listing\Product\Add\NewAsin\Category'));

        return $this->getResult();
    }

    //########################################
}