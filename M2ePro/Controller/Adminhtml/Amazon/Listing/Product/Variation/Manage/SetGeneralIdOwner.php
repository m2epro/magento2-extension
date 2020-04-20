<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage\SetGeneralIdOwner
 */
class SetGeneralIdOwner extends Main
{
    public function execute()
    {
        $listingProductId = $this->getRequest()->getParam('product_id');
        $generalIdOwner = $this->getRequest()->getParam('general_id_owner', null);

        if (empty($listingProductId) || $generalIdOwner === null) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if ($generalIdOwner != \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES) {
            $this->setJsonContent($this->setGeneralIdOwner($listingProductId, $generalIdOwner));

            return $this->getResult();
        }

        $sku = $this->getHelper('Data\Session')->getValue('listing_product_setting_owner_sku_' . $listingProductId);

        if (!$this->hasListingProductSku($listingProductId) && empty($sku)) {
            $this->setJsonContent(['success' => false, 'empty_sku' => true]);

            return $this->getResult();
        }

        $data = $this->setGeneralIdOwner($listingProductId, $generalIdOwner);

        if (!$data['success']) {
            $mainBlock = $this->createBlock('Amazon_Listing_Product_Template_Description');
            $mainBlock->setMessages([
                [
                    'type' => 'warning',
                    'text' => $data['msg']
                ]]);
            $data['html'] = $mainBlock->toHtml();
        } else {
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);
            $listingProduct->getChildObject()->setData('sku', $sku);
            $listingProduct->save();

            $this->getHelper('Data\Session')->removeValue('listing_product_setting_owner_sku_' . $listingProductId);
        }

        $this->setJsonContent($data);

        return $this->getResult();
    }

    private function hasListingProductSku($productId)
    {
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        $sku = $listingProduct->getSku();
        return !empty($sku);
    }

    private function setGeneralIdOwner($productId, $generalIdOwner)
    {
        $data = ['success' => true];

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if ($generalIdOwner == \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES) {
            if (!$amazonListingProduct->isExistDescriptionTemplate()) {
                $data['success'] = false;
                $data['msg'] = $this->__(
                    'Description Policy with enabled ability to create new ASIN(s)/ISBN(s)
                     should be added in order for operation to be finished.'
                );

                return $data;
            }

            if (!$amazonListingProduct->getAmazonDescriptionTemplate()->isNewAsinAccepted()) {
                $data['success'] = false;
                $data['msg'] = $this->__(
                    'Description Policy with enabled ability to create new ASIN(s)/ISBN(s)
                     should be added in order for operation to be finished.'
                );

                return $data;
            }

            $detailsModel = $this->modelFactory->getObject('Amazon_Marketplace_Details');
            $detailsModel->setMarketplaceId($listingProduct->getListing()->getMarketplaceId());
            $themes = $detailsModel->getVariationThemes(
                $amazonListingProduct->getAmazonDescriptionTemplate()->getProductDataNick()
            );

            if (empty($themes)) {
                $data['success'] = false;
                $data['msg'] = $this->__(
                    'The Category chosen in the Description Policy does not support variations.'
                );

                return $data;
            }

            $productAttributes = $amazonListingProduct->getVariationManager()
                ->getTypeModel()
                ->getProductAttributes();

            $isCountEqual = false;
            foreach ($themes as $theme) {
                if (count($theme['attributes']) == count($productAttributes)) {
                    $isCountEqual = true;
                    break;
                }
            }

            if (!$isCountEqual) {
                $data['success'] = false;
                $data['msg'] = $this->__('Number of attributes doesn’t match');

                return $data;
            }
        }

        $listingProduct->getChildObject()->setData('is_general_id_owner', $generalIdOwner)->save();
        $amazonListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();

        return $data;
    }
}
