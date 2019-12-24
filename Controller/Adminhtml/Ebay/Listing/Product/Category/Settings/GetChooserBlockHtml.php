<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\GetChooserBlockHtml
 */
class GetChooserBlockHtml extends Settings
{

    //########################################

    public function execute()
    {
        $ids = $this->getRequest()->getParam('ids', false);

        $ids = $ids ? explode(',', $ids) : [];

        $key = $this->getSessionDataKey();
        $sessionData = $this->getSessionValue($key);

        $neededData = [];

        foreach ($ids as $id) {
            $neededData[$id] = $sessionData[$id];
        }

        // ---------------------------------------
        $listing = $this->getListing();

        $accountId = $listing->getAccountId();
        $marketplaceId = $listing->getMarketplaceId();
        $internalData  = $this->getInternalDataForChooserBlock($neededData);

        /** @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser */
        $chooserBlock = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser');
        $chooserBlock->setDivId('chooser_main_container');
        $chooserBlock->setAccountId($accountId);
        $chooserBlock->setMarketplaceId($marketplaceId);
        $chooserBlock->setInternalData($internalData);

        // ---------------------------------------
        $wrapper = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser_Wrapper');
        $wrapper->setChild('chooser', $chooserBlock);
        // ---------------------------------------

        $this->setAjaxContent($wrapper->toHtml());

        return $this->getResult();
    }

    //########################################

    private function getInternalDataForChooserBlock($data)
    {
        $resultData = [];

        $firstData = reset($data);

        $tempKeys = ['category_main_id',
            'category_main_path',
            'category_main_mode',
            'category_main_attribute'];

        foreach ($tempKeys as $key) {
            $resultData[$key] = $firstData[$key];
        }

        if (!$this->getHelper('Data')->theSameItemsInData($data, $tempKeys)) {
            $resultData['category_main_id'] = 0;
            $resultData['category_main_path'] = null;
            $resultData['category_main_mode'] = \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE;
            $resultData['category_main_attribute'] = null;
            $resultData['category_main_message'] = $this->__(
                'Please, specify a value suitable for all chosen Products.'
            );
        }

        // ---------------------------------------

        $tempKeys = ['category_secondary_id',
            'category_secondary_path',
            'category_secondary_mode',
            'category_secondary_attribute'];

        foreach ($tempKeys as $key) {
            $resultData[$key] = $firstData[$key];
        }

        if (!$this->getHelper('Data')->theSameItemsInData($data, $tempKeys)) {
            $resultData['category_secondary_id'] = 0;
            $resultData['category_secondary_path'] = null;
            $resultData['category_secondary_mode'] = \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE;
            $resultData['category_secondary_attribute'] = null;
            $resultData['category_secondary_message'] = $this->__(
                'Please, specify a value suitable for all chosen Products.'
            );
        }

        // ---------------------------------------

        $tempKeys = ['store_category_main_id',
            'store_category_main_path',
            'store_category_main_mode',
            'store_category_main_attribute'];

        foreach ($tempKeys as $key) {
            $resultData[$key] = $firstData[$key];
        }

        if (!$this->getHelper('Data')->theSameItemsInData($data, $tempKeys)) {
            $resultData['store_category_main_id'] = 0;
            $resultData['store_category_main_path'] = null;
            $resultData['store_category_main_mode'] = \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE;
            $resultData['store_category_main_attribute'] = null;
            $resultData['store_category_main_message'] = $this->__(
                'Please, specify a value suitable for all chosen Products.'
            );
        }

        // ---------------------------------------

        $tempKeys = ['store_category_secondary_id',
            'store_category_secondary_path',
            'store_category_secondary_mode',
            'store_category_secondary_attribute'];

        foreach ($tempKeys as $key) {
            $resultData[$key] = $firstData[$key];
        }

        if (!$this->getHelper('Data')->theSameItemsInData($data, $tempKeys)) {
            $resultData['store_category_secondary_id'] = 0;
            $resultData['store_category_secondary_path'] = null;
            $resultData['store_category_secondary_mode'] =
                \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE;
            $resultData['store_category_secondary_attribute'] = null;
            $resultData['store_category_secondary_message'] = $this->__(
                'Please, specify a value suitable for all chosen Products.'
            );
        }

        // ---------------------------------------

        return $resultData;
    }

    //########################################
}
