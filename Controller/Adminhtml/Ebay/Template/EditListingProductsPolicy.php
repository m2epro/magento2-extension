<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\EditListingProductsPolicy
 */
class EditListingProductsPolicy extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
{
    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (empty($ids)) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $collection = $this->ebayFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('id', ['in' => $ids]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        // ---------------------------------------
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $dataLoader */
        $dataLoader = $this->getHelper('Component_Ebay_Template_Switcher_DataLoader');
        $dataLoader->load($collection);
        // ---------------------------------------

        $initialization = $this->createBlock('Ebay_Listing_Template_Switcher_Initialization');
        $initialization->setMode(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher::MODE_LISTING_PRODUCT);
        $content = $this->createBlock('Ebay_Listing_View_Settings_Edit_Policy');

        $this->setAjaxContent($initialization->toHtml() . $content->toHtml());
        return $this->getResult();
    }

    //########################################
}
