<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\View
 */
class View extends Main
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $id = $this->getRequest()->getParam('id');
            $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $id);

            $this->getHelper('Data\GlobalData')->setValue('view_listing', $listing);
//            Mage::helper('M2ePro/Data_Global')->setValue('marketplace_id', $model->getMarketplaceId());

            $listingView = $this->createBlock('Amazon_Listing_View');

            // Set rule model
            // ---------------------------------------
            $this->setRuleData('amazon_rule_listing_view');
            // ---------------------------------------

            $this->setAjaxContent($listingView->getGridHtml());
            return $this->getResult();
        }

        if ((bool)$this->getRequest()->getParam('do_list', false)) {
            $this->getHelper('Data\Session')->setValue(
                'products_ids_for_list',
                implode(',', $this->getHelper('Data\Session')->getValue('added_products_ids'))
            );

            return $this->_redirect('*/*/*', [
                '_current'  => true,
                'do_list'   => null
            ]);
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
            return $this->_redirect('*/amazon_listing_product_add/index', [
                'id' => $id,
                'not_completed' => 1,
                'step' => 3
            ]);
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

        $this->setPageHelpLink('x/AgItAQ');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Listing "%listing_title%"', $listing->getTitle())
        );

        $this->addContent($this->createBlock('Amazon_Listing_View'));

        // Set rule model
        // ---------------------------------------
        $this->setRuleData('amazon_rule_listing_view');
        // ---------------------------------------

        return $this->getResult();
    }

    protected function setRuleData($prefix)
    {
        $listingData = $this->getHelper('Data\GlobalData')->getValue('view_listing')->getData();

        $storeId = isset($listingData['store_id']) ? (int)$listingData['store_id'] : 0;
        $prefix .= isset($listingData['id']) ? '_'.$listingData['id'] : '';
        $this->getHelper('Data\GlobalData')->setValue('rule_prefix', $prefix);

        // ---------------------------------------
        $useCustomOptions = true;
        $magentoViewMode = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Switcher::VIEW_MODE_MAGENTO;
        $sessionParamName = 'amazonListingView' . $listingData['id'] . 'view_mode';

        if (($this->getRequest()->getParam('view_mode') == $magentoViewMode) ||
            $magentoViewMode == $this->getHelper('Data\Session')->getValue($sessionParamName)) {
            $useCustomOptions = false;
        }
        // ---------------------------------------

        /** @var $ruleModel \Ess\M2ePro\Model\Magento\Product\Rule */
        $ruleModel = $this->activeRecordFactory->getObject('Amazon_Magento_Product_Rule')->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
                'use_custom_options' => $useCustomOptions
            ]
        );

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->getHelper('Data\Session')->setValue(
                $prefix,
                $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif ($ruleParam !== null) {
            $this->getHelper('Data\Session')->setValue($prefix, []);
        }

        $sessionRuleData = $this->getHelper('Data\Session')->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        $this->getHelper('Data\GlobalData')->setValue('rule_model', $ruleModel);
    }
}
