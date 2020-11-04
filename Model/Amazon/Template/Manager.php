<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

class Manager
{
    //########################################

    const COLUMN_PREFIX = 'template';

    const TEMPLATE_DESCRIPTION = 'description';
    const TEMPLATE_PRODUCT_TAX_CODE = 'product_tax_code';
    const TEMPLATE_SELLING_FORMAT = 'selling_format';
    const TEMPLATE_SHIPPING = 'shipping';
    const TEMPLATE_SYNCHRONIZATION = 'synchronization';

    protected $templateNick = null;

    //########################################

    /**
     * @return array
     */
    public function getAllTemplates()
    {
        return [
            self::TEMPLATE_DESCRIPTION,
            self::TEMPLATE_PRODUCT_TAX_CODE,
            self::TEMPLATE_SELLING_FORMAT,
            self::TEMPLATE_SHIPPING,
            self::TEMPLATE_SYNCHRONIZATION
        ];
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getMarketplaceDependentTemplates()
    {
        return [
            self::TEMPLATE_DESCRIPTION,
            self::TEMPLATE_SHIPPING,
            self::TEMPLATE_PRODUCT_TAX_CODE
        ];
    }

    /**
     * @return array
     */
    public function getNotMarketplaceDependentTemplates()
    {
        return array_diff($this->getAllTemplates(), $this->getMarketplaceDependentTemplates());
    }

    //########################################

    /**
     * @return null|string
     */
    public function getTemplate()
    {
        return $this->templateNick;
    }

    /**
     * @param string $nick
     *
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function setTemplate($nick)
    {
        if (!in_array(strtolower($nick), $this->getAllTemplates())) {
            throw new \Ess\M2ePro\Model\Exception('Policy nick is out of knowledge range.');
        }

        $this->templateNick = strtolower($nick);
        return $this;
    }

    //########################################

    /**
     * @return string
     */
    public function getTemplateIdColumnName()
    {
        return self::COLUMN_PREFIX . '_' . $this->getTemplate() . '_id';
    }

    //########################################
}
