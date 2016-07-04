<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class View extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {

            $id = $this->getRequest()->getParam('id');
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);

            $this->getHelper('Data\GlobalData')->setValue('view_listing', $listing);

            // TODO NOT SUPPORTED FEATURES "Advanced search"
//        // Set rule model
//        // ---------------------------------------
//        $this->setRuleData('ebay_rule_view_listing');
//        // ---------------------------------------

            $this->setAjaxContent(
                $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View')->getGridHtml()
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
                'do_list'   => NULL,
                'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View::VIEW_MODE_EBAY
            ));
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);
        } catch (\LogicException $e) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/ebay_listing/index');
        }

        $productAddIds = $listing->getChildObject()->getData('product_add_ids');
        $productAddIds = array_filter((array)json_decode($productAddIds,true));

        if (!empty($productAddIds)) {

            $this->getMessageManager()->addNotice($this->__(
                'Please make sure you finish adding new Products before moving to the next step.'
            ));

            return $this->_redirect('*/ebay_listing_product_category_settings',array('id' => $id, 'step' => 1));
        }

        $this->getHelper('Data\GlobalData')->setValue('view_listing', $listing);

        // TODO NOT SUPPORTED FEATURES "Advanced search"
//        // Set rule model
//        // ---------------------------------------
//        $this->setRuleData('ebay_rule_view_listing');
//        // ---------------------------------------

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Listing "%listing_title%"', $listing->getTitle())
        );

        $this->addContent($this->createBlock('Ebay\Listing\View'));

        return $this->getResult();
    }
}