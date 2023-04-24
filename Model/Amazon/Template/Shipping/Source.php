<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Shipping;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**@var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct;
    /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $shippingTemplateModel */
    private $shippingTemplateModel;

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct): self
    {
        $this->magentoProduct = $magentoProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct(): ?\Ess\M2ePro\Model\Magento\Product
    {
        return $this->magentoProduct;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\Shipping $instance
     *
     * @return $this
     */
    public function setShippingTemplate(\Ess\M2ePro\Model\Amazon\Template\Shipping $instance): self
    {
        $this->shippingTemplateModel = $instance;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Shipping
     */
    public function getShippingTemplate(): ?\Ess\M2ePro\Model\Amazon\Template\Shipping
    {
        return $this->shippingTemplateModel;
    }

    /**
     * @return string
     */
    public function getTemplateId(): string
    {
        return $this->getShippingTemplate()->getTemplateId();
    }
}
