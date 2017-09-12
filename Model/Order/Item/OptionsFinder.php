<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order\Item;

class OptionsFinder extends \Ess\M2ePro\Model\AbstractModel
{
    private $channelOptions = array();

    private $productId = null;

    private $magentoOptions = array();

    private $productType = null;

    private $failedOptions = array();

    //########################################

    /**
     * @param array $options
     * @return $this
     */
    public function setChannelOptions(array $options = array())
    {
        $this->channelOptions = $options;
        return $this;
    }

    /**
     * @param int $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setMagentoOptions(array $options = array())
    {
        $this->magentoOptions = $options;
        return $this;
    }

    public function setProductType($type)
    {
        if (!in_array($type, $this->getAllowedProductTypes())) {
            throw new \Ess\M2ePro\Model\Exception(sprintf('Product type "%s" is not supported.', $type));
        }

        $this->productType = $type;
        return $this;
    }

    //########################################

    /**
     * @return array
     */
    public function getFailedOptions()
    {
        return $this->failedOptions;
    }

    /**
     * @return bool
     */
    public function hasFailedOptions()
    {
        return count($this->failedOptions) > 0;
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductDetails()
    {
        if (is_null($this->productType)) {
            throw new \Ess\M2ePro\Model\Exception('Product type was not set.');
        }

        if ($this->getHelper('Magento\Product')->isGroupedType($this->productType)) {

            $associatedProduct = $this->getGroupedAssociatedProduct();

            if (is_null($associatedProduct)) {
                throw new \Ess\M2ePro\Model\Exception('There is no associated Product found for Grouped Product.');
            }

            return array(
                'associated_options'  => array(),
                'associated_products' => array($associatedProduct->getId())
            );
        }

        $details = $this->getSelectedOptions();
        $details['associated_products'] = $this->prepareAssociatedProducts($details['associated_products']);

        return $details;
    }

    /**
     * @param array $associatedProducts
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function prepareAssociatedProducts(array $associatedProducts)
    {
        $magentoProductHelper = $this->getHelper('Magento\Product');

        if ($magentoProductHelper->isSimpleType($this->productType) ||
            $magentoProductHelper->isDownloadableType($this->productType)) {
            return array($this->productId);
        }

        if ($magentoProductHelper->isBundleType($this->productType)) {
            $bundleAssociatedProducts = array();

            foreach ($associatedProducts as $key => $productIds) {
                $bundleAssociatedProducts[$key] = reset($productIds);
            }

            return $bundleAssociatedProducts;
        }

        if ($magentoProductHelper->isConfigurableType($this->productType)) {
            $configurableAssociatedProducts = array();

            foreach ($associatedProducts as $productIds) {
                if (count($configurableAssociatedProducts) == 0) {
                    $configurableAssociatedProducts = $productIds;
                } else {
                    $configurableAssociatedProducts = array_intersect($configurableAssociatedProducts, $productIds);
                }
            }

            if (count($configurableAssociatedProducts) != 1) {
                throw new \Ess\M2ePro\Model\Exception\Logic('There is no associated Product found for
                    Configurable Product.');
            }

            return $configurableAssociatedProducts;
        }

        if ($magentoProductHelper->isGroupedType($this->productType)) {
            return array_values($associatedProducts);
        }

        return array();
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function getSelectedOptions()
    {
        $channelOptions = $this->toLowerCase($this->channelOptions);

        if (empty($this->magentoOptions)) {
            // product doesn't have required options
            return array(
                'associated_options'  => array(),
                'associated_products' => array()
            );
        }

        $options  = array();
        $products = array();

        // ---------------------------------------

        $configGroup = '/order/magento/settings/';
        $configKey   = 'create_with_first_product_options_when_variation_unavailable';
        $configValue = $this->getHelper('Module')->getConfig()->getGroupValue($configGroup, $configKey);

        if (empty($channelOptions) && !$configValue) {
            return array(
                'associated_options'  => array(),
                'associated_products' => array()
            );
        }

        // Variation info unavailable - return first value for each required option
        // ---------------------------------------
        if (empty($channelOptions)) {
            foreach ($this->magentoOptions as $magentoOption) {
                $optionId = $magentoOption['option_id'];
                $valueId  = $magentoOption['values'][0]['value_id'];

                $options[$optionId] = $valueId;
                $products["{$optionId}::{$valueId}"] = $magentoOption['values'][0]['product_ids'];
            }

            return array(
                'associated_options'  => $options,
                'associated_products' => $products
            );
        }

        // Map variation with magento options
        // ---------------------------------------
        foreach ($this->magentoOptions as $magentoOption) {
            $magentoOption['labels'] = array_filter($magentoOption['labels']);

            $valueLabel = $this->getValueLabel($channelOptions, $magentoOption['labels']);
            if ($valueLabel == '') {
                $this->failedOptions[] = array_shift($magentoOption['labels']);
                continue;
            }

            $magentoValue = $this->getMagentoValue($valueLabel, $magentoOption['values']);
            if (is_null($magentoValue)) {
                $this->failedOptions[] = array_shift($magentoOption['labels']);
                continue;
            }

            $optionId = $magentoOption['option_id'];
            $valueId  = $magentoValue['value_id'];

            $options[$optionId] = $valueId;
            $products["{$optionId}::{$valueId}"] = $magentoValue['product_ids'];
        }

        // ---------------------------------------

        if ($this->getHelper('Magento\Product')->isConfigurableType($this->productType) &&
            $this->hasFailedOptions()) {

            throw new \Ess\M2ePro\Model\Exception('There is no associated Product found for Configurable Product.');
        }

        return array(
            'associated_options'  => $options,
            'associated_products' => $products
        );
    }

    /**
     * Return value label for mapped option if found, empty string otherwise
     *
     * @param array $variation
     * @param array $optionLabels
     *
     * @return string
     */
    private function getValueLabel(array $variation, array $optionLabels)
    {
        $optionLabels = $this->toLowerCase($optionLabels);

        foreach ($optionLabels as $label) {
            if (isset($variation[$label])) {
                return $variation[$label];
            }
        }

        return '';
    }

    /**
     * Return value id for value label if found, null otherwise
     *
     * @param       $valueLabel
     * @param array $optionValues
     *
     * @return int|null
     */
    private function getMagentoValue($valueLabel, array $optionValues)
    {
        foreach ($optionValues as $value) {
            $valueLabels = $this->toLowerCase($value['labels']);

            if (in_array($valueLabel, $valueLabels)) {
                return $value;
            }
        }

        return null;
    }

    private function getGroupedAssociatedProduct()
    {
        $variationName = array_shift($this->channelOptions);

        // ---------------------------------------

        $configGroup = '/order/magento/settings/';
        $configKey   = 'create_with_first_product_options_when_variation_unavailable';
        $configValue = $this->getHelper('Module')->getConfig()->getGroupValue($configGroup, $configKey);

        if ((is_null($variationName) || strlen(trim($variationName)) == 0) && !$configValue) {
            return null;
        }

        // ---------------------------------------

        foreach ($this->magentoOptions as $option) {
            // return product if it's name is equal to variation name
            if (is_null($variationName) || trim(strtolower($option->getName())) == trim(strtolower($variationName))) {
                return $option;
            }
        }

        return null;
    }

    private function toLowerCase(array $data = array())
    {
        if (count($data) == 0) {
            return $data;
        }

        $lowerCasedData = array();

        foreach ($data as $key => $value) {
            $lowerCasedData[trim(strtolower($key))] = trim(strtolower($value));
        }

        return $lowerCasedData;
    }

    private function getAllowedProductTypes()
    {
        return $this->getHelper('Magento\Product')->getOriginKnownTypes();
    }

    //########################################
}
