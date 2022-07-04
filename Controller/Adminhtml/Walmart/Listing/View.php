<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class View extends Main
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->globalData = $globalData;
        $this->sessionHelper = $sessionHelper;
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $id = $this->getRequest()->getParam('id');
            $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $id);

            $this->globalData->setValue('view_listing', $listing);

            $listingView = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View::class);

            // Set rule model
            // ---------------------------------------
            $this->setRuleData('walmart_rule_listing_view');
            // ---------------------------------------

            $this->setAjaxContent($listingView->getGridHtml());
            return $this->getResult();
        }

        if ((bool)$this->getRequest()->getParam('do_list', false)) {
            $this->sessionHelper->setValue(
                'products_ids_for_list',
                implode(',', $this->sessionHelper->getValue('added_products_ids'))
            );

            return $this->_redirect('*/*/*', [
                '_current'  => true,
                'do_list'   => null
            ]);
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $id);
        } catch (\LogicException $e) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/walmart_listing/index');
        }

        $listingProductsIds = $listing->getSetting('additional_data', 'adding_listing_products_ids');

        if (!empty($listingProductsIds)) {
            return $this->_redirect('*/walmart_listing_product_add/index', [
                'id' => $id,
                'not_completed' => 1,
                'step' => 3
            ]);
        }

        // Check listing lock object
        // ---------------------------------------
        if ($listing->isSetProcessingLock('products_in_action')) {
            $this->getMessageManager()->addNotice(
                $this->__('Some Walmart request(s) are being processed now.')
            );
        }
        // ---------------------------------------

        $this->globalData->setValue('view_listing', $listing);

        $this->setPageHelpLink('x/Y-1IB');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Listing "%listing_title%"', $listing->getTitle())
        );

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View::class));

        // Set rule model
        // ---------------------------------------
        $this->setRuleData('walmart_rule_listing_view');
        // ---------------------------------------

        return $this->getResult();
    }

    protected function setRuleData($prefix)
    {
        $listingData = $this->globalData->getValue('view_listing')->getData();

        $storeId = isset($listingData['store_id']) ? (int)$listingData['store_id'] : 0;
        $prefix .= isset($listingData['id']) ? '_'.$listingData['id'] : '';
        $this->globalData->setValue('rule_prefix', $prefix);

        // ---------------------------------------
        $useCustomOptions = true;
        $magentoViewMode = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Switcher::VIEW_MODE_MAGENTO;
        $sessionParamName = 'walmartListingView' . $listingData['id'] . 'view_mode';

        if (($this->getRequest()->getParam('view_mode') == $magentoViewMode) ||
            $magentoViewMode == $this->sessionHelper->getValue($sessionParamName)) {
            $useCustomOptions = false;
        }
        // ---------------------------------------

        /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
        $ruleModel = $this->activeRecordFactory->getObject('Walmart_Magento_Product_Rule')->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
                'use_custom_options' => $useCustomOptions
            ]
        );

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->sessionHelper->setValue(
                $prefix,
                $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif ($ruleParam !== null) {
            $this->sessionHelper->setValue($prefix, []);
        }

        $sessionRuleData = $this->sessionHelper->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        $this->globalData->setValue('rule_model', $ruleModel);
    }
}
