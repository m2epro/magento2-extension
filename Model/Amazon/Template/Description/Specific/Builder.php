<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description\Specific;

use Ess\M2ePro\Model\Amazon\Template\Description\Specific;

/**
 * Class Ess\M2ePro\Model\Amazon\Template\Description\Specific\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    private $templateDescriptionId;

    //########################################

    public function setTemplateDescriptionId($templateDescriptionId)
    {
        $this->templateDescriptionId = $templateDescriptionId;
    }

    public function getTemplateDescriptionId()
    {
        if (empty($this->templateDescriptionId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('descriptionTemplateId not set');
        }

        return $this->templateDescriptionId;
    }

    //########################################

    protected function prepareData()
    {
        return [
            'template_description_id' => $this->getTemplateDescriptionId(),
            'xpath'             => $this->rawData['xpath'],
            'mode'              => $this->rawData['mode'],
            'is_required'       => isset($this->rawData['is_required']) ? $this->rawData['is_required'] : 0,
            'recommended_value' => $this->rawData['mode'] == Specific::DICTIONARY_MODE_RECOMMENDED_VALUE
                ? $this->rawData['recommended_value'] : '',
            'custom_value'      => $this->rawData['mode'] == Specific::DICTIONARY_MODE_CUSTOM_VALUE
                ? $this->rawData['custom_value'] : '',
            'custom_attribute'  => $this->rawData['mode'] == Specific::DICTIONARY_MODE_CUSTOM_ATTRIBUTE
                ? $this->rawData['custom_attribute'] : '',
            'type'              => isset($this->rawData['type']) ? $this->rawData['type'] : '',
            'attributes'        => isset($this->rawData['attributes']) ?
                $this->getHelper('Data')->jsonEncode($this->rawData['attributes']) : '[]'
        ];
    }

    public function getDefaultData()
    {
        return [];
    }

    //########################################
}
