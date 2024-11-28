<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated;

use Ess\M2ePro\Model\Magento\Product\Type\Configurable;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Magento\Product $magentoProduct
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated $shippingCalculatedTemplateModel
     */
    private $shippingCalculatedTemplateModel = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated $instance
     *
     * @return $this
     */
    public function setShippingCalculatedTemplate(\Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated $instance)
    {
        $this->shippingCalculatedTemplateModel = $instance;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated
     */
    public function getShippingCalculatedTemplate()
    {
        return $this->shippingCalculatedTemplateModel;
    }

    //########################################

    /**
     * @return string
     */
    public function getPackageSize()
    {
        $src = $this->getShippingCalculatedTemplate()->getPackageSizeSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    /**
     * @return array
     */
    public function getDimension()
    {
        $src = $this->getShippingCalculatedTemplate()->getDimensionSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::DIMENSION_NONE) {
            return [];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::DIMENSION_CUSTOM_ATTRIBUTE) {
            $widthValue = str_replace(',', '.', $this->getMagentoProduct()->getAttributeValue($src['width_attribute']));
            $lengthValue = str_replace(
                ',',
                '.',
                $this->getMagentoProduct()->getAttributeValue($src['length_attribute'])
            );
            $depthValue = str_replace(',', '.', $this->getMagentoProduct()->getAttributeValue($src['depth_attribute']));

            return [
                'width' => $widthValue,
                'length' => $lengthValue,
                'depth' => $depthValue,
            ];
        }

        return [
            'width' => $src['width_value'],
            'length' => $src['length_value'],
            'depth' => $src['depth_value'],
        ];
    }

    /**
     * @return array{minor: float, major: int}
     */
    public function getWeight(): array
    {
        $src = $this->getShippingCalculatedTemplate()->getWeightSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE) {
            $weightValue = $this->getWeightValue($src['attribute'], $this->getMagentoProduct());
            $weightValue = str_replace(',', '.', $weightValue);
            $weightArray = explode('.', $weightValue);

            $minor = $major = 0;
            if (count($weightArray) >= 2) {
                [$major, $minor] = $weightArray;

                if ($minor > 0 && $this->getShippingCalculatedTemplate()->isMeasurementSystemEnglish()) {
                    $minor = ($minor / pow(10, strlen($minor))) * 16;
                    $minor = ceil($minor);
                    if ($minor == 16) {
                        ++$major;
                        $minor = 0;
                    }
                }

                if ($minor > 0 && $this->getShippingCalculatedTemplate()->isMeasurementSystemMetric()) {
                    $minor = ($minor / pow(10, strlen($minor))) * 1000;
                    $minor = ceil($minor);
                    if ($minor == 1000) {
                        ++$major;
                        $minor = 0;
                    }
                }

                $minor < 0 && $minor = 0;
            } else {
                $major = (int)$weightValue;
            }

            return [
                'minor' => (float)$minor,
                'major' => (int)$major,
            ];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::WEIGHT_NONE) {
            return [
                'minor' => 0,
                'major' => 0,
            ];
        }

        return [
            'minor' => (float)$src['minor'],
            'major' => (int)$src['major'],
        ];
    }

    /**
     * @return float|int|string
     */
    private function getWeightValue(string $attributeCode, \Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $value = $magentoProduct->getAttributeValue($attributeCode);
        if (!empty($value)) {
            return $value;
        }

        if ($magentoProduct->isGroupedType()) {
            return $magentoProduct->getGroupedWeight();
        }

        return $magentoProduct->getVariationMaxWeight($attributeCode);
    }
}
