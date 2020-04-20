<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage\SetVariationTheme
 */
class SetVariationTheme extends Main
{
    public function execute()
    {
        $listingProductId = $this->getRequest()->getParam('product_id');
        $variationTheme   = $this->getRequest()->getParam('variation_theme', null);

        if (empty($listingProductId) || $variationTheme === null) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $parentTypeModel = $amazonListingProduct->getVariationManager()->getTypeModel();

        $result = ['success' => true];

        if ($parentTypeModel->getChannelTheme() == $variationTheme) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $parentTypeModel->setChannelTheme($variationTheme, true, false);

        $variationHelper = $this->getHelper('Component_Amazon_Variation');
        $variationHelper->increaseThemeUsageCount($variationTheme, $listingProduct->getMarketplace()->getId());

        $productDataNick = $amazonListingProduct->getAmazonDescriptionTemplate()->getProductDataNick();

        $marketplaceDetails = $this->modelFactory->getObject('Amazon_Marketplace_Details');
        $marketplaceDetails->setMarketplaceId($amazonListingProduct->getMarketplace()->getId());

        $themeAttributes   = $marketplaceDetails->getVariationThemeAttributes($productDataNick, $variationTheme);
        $productAttributes = $parentTypeModel->getProductAttributes();

        if (count($themeAttributes) != 1 || count($productAttributes) != 1) {
            $parentTypeModel->getProcessor()->process();
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $productAttribute = reset($productAttributes);
        $themeAttribute   = reset($themeAttributes);

        $parentTypeModel->setMatchedAttributes([$productAttribute => $themeAttribute], true);
        $parentTypeModel->getProcessor()->process();

        if ($productAttribute == $themeAttribute || $listingProduct->getMagentoProduct()->isGroupedType()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper */
        $vocabularyHelper = $this->getHelper('Component_Amazon_Vocabulary');

        if ($vocabularyHelper->isAttributeAutoActionDisabled()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        if ($vocabularyHelper->isAttributeExistsInLocalStorage($productAttribute, $themeAttribute)) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        if ($vocabularyHelper->isAttributeExistsInServerStorage($productAttribute, $themeAttribute)) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        if ($vocabularyHelper->isAttributeAutoActionNotSet()) {
            $result['vocabulary_attributes'] = [$productAttribute => $themeAttribute];
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $vocabularyHelper->addAttribute($productAttribute, $themeAttribute);

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
