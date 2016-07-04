<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $descriptionTemplateModel \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

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
     * @param \Ess\M2ePro\Model\Template\Description $instance
     * @return $this
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->descriptionTemplateModel;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description
     */
    public function getAmazonDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return null|string
     */
    public function getWorldwideId()
    {
        $result = '';
        $src = $this->getAmazonDescriptionTemplate()->getWorldwideIdSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Template\Description::WORLDWIDE_ID_MODE_NONE) {
            $result = NULL;
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Template\Description::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        is_string($result) && $result = trim($result);

        return $result;
    }

    //########################################
}