<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Category\Specific;

use Ess\M2ePro\Model\Walmart\Template\Category\Specific as Specific;

/**
 * Class Ess\M2ePro\Model\Walmart\Template\Category\Specific\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    private $templateCategoryId;

    //########################################

    public function setTemplateCategoryId($descriptionTemplateId)
    {
        $this->templateCategoryId = $descriptionTemplateId;
    }

    public function getTemplateCategoryId()
    {
        if (empty($this->templateCategoryId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('categoryTemplateId not set');
        }

        return $this->templateCategoryId;
    }

    //########################################

    protected function prepareData()
    {
        return [
            'template_category_id' => $this->getTemplateCategoryId(),
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
