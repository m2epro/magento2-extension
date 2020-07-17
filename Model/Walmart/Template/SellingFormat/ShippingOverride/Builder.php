<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride;

/**
 * Class Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    private $templateSellingFormatId;

    //########################################

    public function setTemplateSellingFormatId($templateSellingFormatId)
    {
        $this->templateSellingFormatId = $templateSellingFormatId;
    }

    public function getTemplateSellingFormatId()
    {
        if (empty($this->templateSellingFormatId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('templateSellingFormatId not set');
        }

        return $this->templateSellingFormatId;
    }

    //########################################

    protected function prepareData()
    {
        return [
            'template_selling_format_id' => $this->getTemplateSellingFormatId(),

            'method'              => $this->rawData['method'],
            'is_shipping_allowed' => $this->rawData['is_shipping_allowed'],
            'region'              => $this->rawData['region'],
            'cost_mode'           => !empty($this->rawData['cost_mode']) ? $this->rawData['cost_mode'] : 0,
            'cost_value'          => !empty($this->rawData['cost_value']) ? $this->rawData['cost_value'] : 0,
            'cost_attribute'      => !empty($this->rawData['cost_attribute']) ? $this->rawData['cost_attribute'] : ''
        ];
    }

    public function getDefaultData()
    {
        return [];
    }

    //########################################
}
