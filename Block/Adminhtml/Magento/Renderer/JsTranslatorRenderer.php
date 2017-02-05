<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class JsTranslatorRenderer extends AbstractRenderer
{
    protected $jsTranslations = [];

    public function add($alias, $translation)
    {
        $this->jsTranslations[$alias] = $translation;
        return $this;
    }

    public function addTranslations(array $translations)
    {
        $this->jsTranslations = array_merge($this->jsTranslations, $translations);
        return $this;
    }

    public function render()
    {
        if (empty($this->jsTranslations)) {
            return '';
        }

        $translations = $this->helperFactory->getObject('Data')->jsonEncode($this->jsTranslations);

        return "M2ePro.translator.add({$translations});";
    }

}