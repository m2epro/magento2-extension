<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Existence
 */
class Existence extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    private $existenceResult = [];

    /**
     * @param array $result
     * @return $this
     */
    public function setExistenceResult(array $result)
    {
        $this->existenceResult = $result;
        return $this;
    }

    //########################################

    /**
     * @return bool
     */
    public function validate()
    {
        if (empty($this->existenceResult['asin'])) {
            return true;
        }

        if (empty($this->existenceResult['info'])) {
            $this->addMessage(
                'There is an unexpected error appeared during the process of linking Magento Product
                 to Amazon Product. The data was not sent back from Amazon.'
            );

            return false;
        }

        if (!$this->getVariationManager()->isRelationMode()) {
            $this->processSimpleOrIndividualProduct();
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            $this->processParentProduct();
        }

        if ($this->getVariationManager()->isRelationChildType()) {
            $this->processChildProduct();
        }

        return false;
    }

    //########################################

    private function processSimpleOrIndividualProduct()
    {
        $asin = $this->existenceResult['asin'];
        $info = $this->existenceResult['info'];

        if (!empty($info['type']) && $info['type'] == 'parent') {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because in your Inventory the provided SKU %sku%
                     is assigned to the Parent Product (ASIN/ISBN: "%asin%") while you are trying to List a Child or
                     Simple Product. Please check the Settings and try again.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        if ($this->getAmazonListingProduct()->getGeneralId() &&
            $this->getAmazonListingProduct()->getGeneralId() != $asin
        ) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because in your Inventory the provided SKU "%sku%" is assigned
                     to the Product with different ASIN/ISBN (%asin%). Please check the Settings and try again.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        $this->link($asin, $this->getData('sku'));
    }

    private function processParentProduct()
    {
        $asin = $this->existenceResult['asin'];
        $info = $this->existenceResult['info'];

        if (empty($info['type']) || $info['type'] != 'parent') {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because in your Inventory the provided SKU "%sku%" is assigned
                     to the Child or Simple Product (ASIN/ISBN: "%asin%") while you want to list Parent Product.
                     Please check the Settings and try again.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        if (!empty($info['bad_parent'])) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because working with Amazon Parent Product (ASIN/ISBN: "%asin%")
                     found by SKU "%sku%" is limited due to Amazon API restrictions.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        $magentoAttributes = $this->getVariationManager()->getTypeModel()->getProductAttributes();

        if (count($magentoAttributes) != count($info['variation_attributes'])) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because the number of Variation Attributes of
                     the Amazon Parent Product (ASIN/ISBN: "%asin%") found by SKU "%sku%" does not match the number of
                     Variation Attributes of the Magento Parent Product.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        $this->link($this->existenceResult['asin'], $this->getData('sku'));
    }

    private function processChildProduct()
    {
        $asin = $this->existenceResult['asin'];
        $info = $this->existenceResult['info'];

        if (empty($info['type']) || $info['type'] != 'child') {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because Product found on Amazon (ASIN/ISBN: "%asin%") by SKU "%sku%"
                     is not a Child Product.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        if (!empty($info['bad_parent'])) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because Item found on Amazon (ASIN/ISBN: "%asin%") by SKU "%sku%"
                     is a Child Product of the Parent Product (ASIN/ISBN: "%parent_asin%")
                     access to which limited by Amazon API restriction.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin, '!parent_asin' => $info['parent_asin']]
                )
            );

            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
        $parentAmazonListingProduct = $this->getVariationManager()
            ->getTypeModel()
            ->getParentListingProduct()
            ->getChildObject();

        if ($parentAmazonListingProduct->getGeneralId() != $info['parent_asin']) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because in your Inventory the provided SKU "%sku%" is assigned
                     to the Amazon Child Product (ASIN/ISBN: "%asin%") related to the Amazon Parent Product
                     (ASIN/ISBN: "%parent_asin%") with different ASIN/ISBN. Please check the Settings and try again.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin, '!parent_asin' => $info['parent_asin']]
                )
            );

            return;
        }

        $generalId = $this->getAmazonListingProduct()->getGeneralId();

        if (!empty($generalId) && $generalId != $asin) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because in your Inventory the provided SKU "%sku%" is
                    assigned to the Amazon Product (ASIN/ISBN: "%asin%") with different ASIN/ISBN.
                    Please check the Settings and try again.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        $parentChannelVariations = $parentAmazonListingProduct->getVariationManager()
            ->getTypeModel()
            ->getChannelVariations();

        if (!isset($parentChannelVariations[$asin])) {
            $this->addMessage(
                'Product cannot be Listed because the respective Parent has no Child Product
                 with required combination of the Variation Attributes values.'
            );

            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $childProductCollection */
        $childProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $childProductCollection->addFieldToFilter('variation_parent_id', $parentAmazonListingProduct->getId());
        if (!empty($generalId)) {
            $childProductCollection->addFieldToFilter('general_id', ['neq' => $generalId]);
        }

        $childGeneralIds = $childProductCollection->getColumnValues('general_id');

        if (in_array($asin, $childGeneralIds)) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed because ASIN/ISBN "%asin%" found on Amazon by SKU "%sku%" has already been
                     used by you to link another Magento Product to Amazon Product.',
                    ['!sku' => $this->getData('sku'), '!asin' => $asin]
                )
            );

            return;
        }

        $this->link($this->existenceResult['asin'], $this->getData('sku'));
    }

    //########################################

    private function link($generalId, $sku)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Linking $linkingObject */
        $linkingObject = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Type_ListAction_Linking');
        $linkingObject->setListingProduct($this->getListingProduct());
        $linkingObject->setGeneralId($generalId);
        $linkingObject->setSku($sku);

        if ($linkingObject->link()) {
// M2ePro\TRANSLATIONS
// Product has been found by SKU "%sku%" in your Inventory and successfully linked.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product has been found by SKU "%sku%" in your Inventory and successfully linked.',
                    ['!sku' => $sku]
                ),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
            );

            return;
        }

        $this->addMessage(
            $this->getHelper('Module\Log')->encodeDescription(
                'Unexpected error during process of linking by SKU "%sku%".
                 The required SKU has been found but the data is not sent back. Please try again.',
                ['!sku' => $sku]
            )
        );
    }

    //########################################
}
