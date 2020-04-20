<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoGetSuggestedCategory
 */
class StepTwoGetSuggestedCategory extends Settings
{

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingProductIds = $this->getRequestIds('products_id');
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->getListing();
        $marketplaceId = (int)$listing->getData('marketplace_id');
        // ---------------------------------------

        // ---------------------------------------
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $listing->getChildObject()->getResource()->getProductCollection($listingId);
        $collection->addAttributeToSelect('name');
        $collection->getSelect()->where('lp.id IN (?)', $listingProductIds);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            $this->setJsonContent([]);
            return $this->getResult();
        }

        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

        $result = ['failed' => 0, 'succeeded' => 0];

        // ---------------------------------------
        foreach ($collection->getItems() as $product) {
            if (($query = $product->getData('name')) == '') {
                $result['failed']++;
                continue;
            }

            $attributeSetId = $product->getData('attribute_set_id');
            if (!$this->getHelper('Magento\AttributeSet')->isDefault($attributeSetId)) {
                $query .= ' ' . $this->getHelper('Magento\AttributeSet')->getName($attributeSetId);
            }

            try {
                $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
                $connectorObj = $dispatcherObject->getConnector(
                    'category',
                    'get',
                    'suggested',
                    ['query' => $query],
                    $marketplaceId
                );

                $dispatcherObject->process($connectorObj);
                $suggestions = $connectorObj->getResponseData();
            } catch (\Exception $e) {
                $result['failed']++;
                continue;
            }

            if (!empty($suggestions)) {
                foreach ($suggestions as $key => $suggestion) {
                    if (!is_numeric($key)) {
                        unset($suggestions[$key]);
                    }
                }
            }

            if (empty($suggestions)) {
                $result['failed']++;
                continue;
            }

            $suggestedCategory = reset($suggestions);

            $categoryExists = $this->getHelper('Component_Ebay_Category_Ebay')
                ->exists(
                    $suggestedCategory['category_id'],
                    $marketplaceId
                );

            if (!$categoryExists) {
                $result['failed']++;
                continue;
            }

            $listingProductId = $product->getData('listing_product_id');
            $listingProductData = $sessionData['mode_product'][$listingProductId];
            $listingProductData['category_main_mode'] = \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY;
            $listingProductData['category_main_id'] = $suggestedCategory['category_id'];
            $listingProductData['category_main_path'] = implode(' > ', $suggestedCategory['category_path']);

            $sessionData['mode_product'][$listingProductId] = $listingProductData;

            $result['succeeded']++;
        }
        // ---------------------------------------

        $this->getHelper('Data\Session')->setValue($this->sessionKey, $sessionData);

        $this->setJsonContent($result);

        return $this->getResult();
    }

    //########################################
}
