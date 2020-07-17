<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;
use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoGetSuggestedCategory
 */
class StepTwoGetSuggestedCategory extends Settings
{
    //########################################

    public function execute()
    {
        $listing = $this->getListingFromRequest();

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->getEbayListingFromRequest()->getResource()->getProductCollection($listing->getId());
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToFilter('listing_product_id', ['in' => $this->getRequestIds('products_id')]);

        if ($collection->getSize() == 0) {
            $this->setJsonContent([]);
            return $this->getResult();
        }

        $sessionData = $this->getSessionValue($this->getSessionDataKey());
        $result = ['failed' => 0, 'succeeded' => 0];

        foreach ($collection->getItems() as $product) {
            $lpId = $product->getData('listing_product_id');
//            if (!empty($sessionData[$lpId][eBayCategory::TYPE_EBAY_MAIN]['value'])) {
//                continue;
//            }

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
                    $listing->getMarketplaceId()
                );

                $dispatcherObject->process($connectorObj);
                $suggestions = $connectorObj->getResponseData();
            } catch (\Exception $e) {
                $this->getHelper('Module\Exception')->process($e);
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

            $suggestedCategory = null;
            foreach ($suggestions as $suggestion) {
                $categoryExists = $this->getHelper('Component_Ebay_Category_Ebay')->exists(
                    $suggestion['category_id'],
                    $listing->getMarketplaceId()
                );

                if ($categoryExists) {
                    $suggestedCategory = $suggestion;
                    break;
                }
            }

            if ($suggestedCategory === null) {
                $result['failed']++;
                continue;
            }

            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
            $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            $template->loadByCategoryValue(
                $suggestedCategory['category_id'],
                TemplateCategory::CATEGORY_MODE_EBAY,
                $listing->getMarketplaceId(),
                0
            );

            $sessionData[$lpId][eBayCategory::TYPE_EBAY_MAIN] = [
                'mode'               => TemplateCategory::CATEGORY_MODE_EBAY,
                'value'              => $suggestedCategory['category_id'],
                'path'               => implode('>', $suggestedCategory['category_path']),
                'is_custom_template' => $template->getIsCustomTemplate(),
                'template_id'        => $template->getId(),
                'specific'           => null
            ];
            $sessionData[$lpId]['listing_products_ids'] = [$lpId];

            $result['succeeded']++;
        }

        $this->setSessionValue($this->getSessionDataKey(), $sessionData);
        $this->setJsonContent($result);

        return $this->getResult();
    }

    //########################################
}
