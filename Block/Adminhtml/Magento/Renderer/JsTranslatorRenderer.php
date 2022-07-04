<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class JsTranslatorRenderer extends AbstractRenderer
{
    protected $jsTranslations = [];

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        parent::__construct($helperFactory);
        $this->dataHelper = $dataHelper;
    }

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

        $translations = $this->dataHelper->jsonEncode($this->jsTranslations);

        return "M2ePro.translator.add({$translations});";
    }
}
