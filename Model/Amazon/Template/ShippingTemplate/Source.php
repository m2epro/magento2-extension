<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ShippingTemplate;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Magento\Product $magentoProduct
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate $shippingTemplateModel
     */
    private $shippingTemplateModel = null;

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
     * @param \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate $instance
     * @return $this
     */
    public function setShippingTemplate(\Ess\M2ePro\Model\Amazon\Template\ShippingTemplate $instance)
    {
        $this->shippingTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate
     */
    public function getShippingTemplate()
    {
        return $this->shippingTemplateModel;
    }

    //########################################

    /**
     * @return string
     */
    public function getTemplateName()
    {
        $result = '';

        switch ($this->getShippingTemplate()->getTemplateNameMode()) {
            case \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate::TEMPLATE_NAME_VALUE:
                $result = $this->getShippingTemplate()->getTemplateNameValue();
                break;

            case \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate::TEMPLATE_NAME_ATTRIBUTE:
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getShippingTemplate()->getTemplateNameAttribute()
                );
                break;
        }

        return $result;
    }

    //########################################
}