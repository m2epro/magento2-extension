<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class View extends Main
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {

            $id = $this->getRequest()->getParam('id');
            $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $id);

            $this->getHelper('Data\GlobalData')->setValue('view_listing', $listing);
//            Mage::helper('M2ePro/Data_Global')->setValue('marketplace_id', $model->getMarketplaceId());

            // TODO NOT SUPPORTED FEATURES "Advanced search"
//            // Set rule model
//            // ---------------------------------------
//            $this->setRuleData('amazon_rule_listing_view');
//            // ---------------------------------------

            $this->setAjaxContent(
                $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View')->getGridHtml()
            );
            return $this->getResult();
        }

        if ((bool)$this->getRequest()->getParam('do_list', false)) {
            $this->getHelper('Data\Session')->setValue(
                'products_ids_for_list',
                implode(',', $this->getHelper('Data\Session')->getValue('added_products_ids'))
            );

            return $this->_redirect('*/*/*', array(
                '_current'  => true,
                'do_list'   => NULL
            ));
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $id);
        } catch (\LogicException $e) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/amazon_listing/index');
        }

        $listingProductsIds = $listing->getSetting('additional_data', 'adding_listing_products_ids');

        if (!empty($listingProductsIds)) {
            return $this->_redirect('*/amazon_listing_product_add/index', array(
                'id' => $id,
                'not_completed' => 1,
                'step' => 3
            ));
        }

        // Check listing lock object
        // ---------------------------------------
        if ($listing->isSetProcessingLock('products_in_action')) {
            $this->getMessageManager()->addNotice(
                $this->__('Some Amazon request(s) are being processed now.')
            );
        }
        // ---------------------------------------

        $this->getHelper('Data\GlobalData')->setValue('view_listing', $listing);

        // TODO NOT SUPPORTED FEATURES "Advanced search"
//        // Set rule model
//        // ---------------------------------------
//        $this->setRuleData('amazon_rule_listing_view');
//        // ---------------------------------------
        
        $this->setPageHelpLink(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Manage+M2E+Pro+Listings');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Listing "%listing_title%"', $listing->getTitle())
        );

//        $a = $this->createBlock('Amazon\Listing\View')->toHtml();

        $this->addContent($this->createBlock('Amazon\Listing\View'));

        return $this->getResult();
    }
}