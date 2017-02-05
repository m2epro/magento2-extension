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

            // Set rule model
            // ---------------------------------------
            $this->setRuleData('ebay_rule_view_listing');
            // ---------------------------------------

            $this->setAjaxContent(
                $this->createBlock('Ebay\Listing\View')->getGridHtml()
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
                'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::VIEW_MODE_EBAY
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
        $productAddIds = array_filter((array)$this->getHelper('Data')->jsonDecode($productAddIds));

        if (!empty($productAddIds)) {

            $this->getMessageManager()->addNotice($this->__(
                'Please make sure you finish adding new Products before moving to the next step.'
            ));

            return $this->_redirect('*/ebay_listing_product_category_settings',array('id' => $id, 'step' => 1));
        }

        $this->getHelper('Data\GlobalData')->setValue('view_listing', $listing);

        // Set rule model
        // ---------------------------------------
        $this->setRuleData('ebay_rule_view_listing');
        // ---------------------------------------

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Listing "%listing_title%"', $listing->getTitle())
        );

        $this->addContent($this->createBlock('Ebay\Listing\View'));

        return $this->getResult();
    }

    //########################################

    protected function setRuleData($prefix)
    {
        $listingData = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        $storeId = isset($listingData['store_id']) ? (int)$listingData['store_id'] : 0;
        $prefix .= isset($listingData['id']) ? '_'.$listingData['id'] : '';
        $this->getHelper('Data\GlobalData')->setValue('rule_prefix', $prefix);

        $ruleModel = $this->activeRecordFactory->getObject('Ebay\Magento\Product\Rule')->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
            ]
        );

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->getHelper('Data\Session')->setValue(
                $prefix, $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif (!is_null($ruleParam)) {
            $this->getHelper('Data\Session')->setValue($prefix, []);
        }

        $sessionRuleData = $this->getHelper('Data\Session')->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        $this->getHelper('Data\GlobalData')->setValue('rule_model', $ruleModel);
    }
}