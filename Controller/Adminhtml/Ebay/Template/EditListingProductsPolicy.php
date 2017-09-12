<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

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
        $dataLoader = $this->getHelper('Component\Ebay\Template\Switcher\DataLoader');
        $dataLoader->load($collection);
        // ---------------------------------------

        $initialization = $this->createBlock('Ebay\Listing\Template\Switcher\Initialization');
        $initialization->setMode(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher::MODE_LISTING_PRODUCT);
        $content = $this->createBlock('Ebay\Listing\View\Settings\Edit\Policy');

        $this->setAjaxContent($initialization->toHtml() . $content->toHtml());
        return $this->getResult();
    }

    //########################################
}