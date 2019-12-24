<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Category\Specific;

use Ess\M2ePro\Model\Walmart\Template\Category\Specific as CategorySpecific;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Category\Specific\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $descriptionSpecificTemplateModel \Ess\M2ePro\Model\Walmart\Template\Category\Specific
     */
    private $descriptionSpecificTemplateModel = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
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
     * @param \Ess\M2ePro\Model\Walmart\Template\Category\Specific $instance
     * @return $this
     */
    public function setCategorySpecificTemplate(\Ess\M2ePro\Model\Walmart\Template\Category\Specific $instance)
    {
        $this->descriptionSpecificTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Category\Specific
     */
    public function getCategorySpecificTemplate()
    {
        return $this->descriptionSpecificTemplateModel;
    }

    //########################################

    /**
     * @return string
     */
    public function getPath()
    {
        $xpath = $this->getCategorySpecificTemplate()->getXpath();
        $xpathParts = explode('/', $xpath);

        $path = '';
        $isFirst = true;

        foreach ($xpathParts as $part) {
            list($tag, $index) = explode('-', $part);

            if (!$tag) {
                continue;
            }

            $isFirst || $path .= '{"childNodes": ';
            $path .= "{\"$tag\": {\"$index\": ";
            $isFirst = false;
        }

        $templateObj = $this->getCategorySpecificTemplate();

        if ($templateObj->isModeNone()) {
            $path .= '[]';
            $path .= str_repeat('}', substr_count($path, '{'));

            return $path;
        }

        $path .= '%data%';
        $path .= str_repeat('}', substr_count($path, '{'));

        $encodedValue = $this->getHelper('Data')->jsonEncode($this->getValue());
        $path = str_replace(
            '%data%',
            '{"value": ' . $encodedValue . ',"attributes": ' . $this->getValueAttributes() . '}',
            $path
        );

        return $path;
    }

    public function getValue()
    {
        $templateObj = $this->getCategorySpecificTemplate();

        if ($templateObj->isModeNone()) {
            return false;
        }

        $value = $templateObj->getData($templateObj->getMode());

        if ($templateObj->isModeCustomAttribute()) {
            $value = $this->getMagentoProduct()->getAttributeValue($value);
            /**
             * Explode values of "Unit/Measure" to a separate specifics. For example, 100.20MB to 100.20 and MB
             * Measure specific has type FLOAT and will be stripped of redundant data
             */
            if ($templateObj->getCode() == CategorySpecific::UNIT_SPECIFIC_CODE) {
                $value = trim(str_replace((float)$value, '', $value));
            }
        }

        $templateObj->isTypeInt() && $value = (int)$value;
        $templateObj->isTypeFloat() && $value = (float)str_replace(',', '.', $value);
        $templateObj->isTypeDateTime() && $value = str_replace(' ', 'T', $value);

        return $value;
    }

    public function getValueAttributes()
    {
        $templateObj = $this->getCategorySpecificTemplate();

        $attributes = [];

        foreach ($templateObj->getAttributes() as $index => $attribute) {
            list($attributeName) = array_keys($attribute);

            $attributeData = $attribute[$attributeName];

            $attributeValue = $attributeData['mode'] == CategorySpecific::DICTIONARY_MODE_CUSTOM_VALUE
                ? $attributeData['custom_value']
                : $this->getMagentoProduct()->getAttributeValue($attributeData['custom_attribute']);

            $attributes[$index] = [
                'name'  => str_replace(' ', '', $attributeName),
                'value' => $attributeValue,
            ];
        }

        return $this->getHelper('Data')->jsonEncode($attributes);
    }

    //########################################
}
