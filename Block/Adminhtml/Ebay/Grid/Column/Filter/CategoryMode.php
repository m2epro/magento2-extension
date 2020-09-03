<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter;

use \Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\CategoryMode
 */
class CategoryMode extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Select
{
    use Traits\BlockTrait;
    use Traits\RendererTrait;

    const MODE_NOT_SELECTED = 0;
    const MODE_SELECTED     = 1;
    const MODE_EBAY         = 2;
    const MODE_ATTRIBUTE    = 3;
    const MODE_TITLE        = 10;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    //########################################

    public function __construct(
        Renderer\CssRenderer $css,
        Renderer\JsPhpRenderer $jsPhp,
        Renderer\JsRenderer $js,
        Renderer\JsTranslatorRenderer $jsTranslatorRenderer,
        Renderer\JsUrlRenderer $jsUrlRenderer,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        array $data = []
    ) {
        parent::__construct($context, $resourceHelper, $data);

        $this->helperFactory = $helperFactory;
        $this->css = $css;
        $this->jsPhp = $jsPhp;
        $this->js = $js;
        $this->jsTranslator = $jsTranslatorRenderer;
        $this->jsUrl = $jsUrlRenderer;
    }

    //########################################

    public function getHtml()
    {
        $value = $this->getValue();

        $titleValue = !empty($value['title']) ? $value['title'] : '';
        $isAjax = $this->getHelper('Data')->jsonEncode($this->getRequest()->isAjax());
        $modeTitle = self::MODE_TITLE;

        $this->js->add(<<<JS
    (function() {

        var initObservers = function () {

         $('{$this->_getHtmlId()}')
            .observe('change', function() {

                var div = $('{$this->_getHtmlId()}_title_container');
                div.hide();

                if (this.value == '{$modeTitle}') {
                    div.show();
                }
            })
            .simulate('change');
         };

         Event.observe(window, 'load', initObservers);
         if ({$isAjax}) {
             initObservers();
         }

    })();
JS
        );

        $html = <<<HTML
<div id="{$this->_getHtmlId()}_title_container" style="display: none;">
    <div style="width: auto; padding-top: 5px;">
        <span>{$this->getHelper('Module\Translation')->__('Category Path / Category ID')}: </span><br>
        <input style="width: 300px;" type="text" value="{$titleValue}" name="{$this->getColumn()->getId()}[title]">
    </div>
</div>
HTML;

        return parent::getHtml() . $html;
    }

    //########################################

    public function getValue()
    {
        $value = $this->getData('value');

        if (is_array($value) &&
            (isset($value['mode']) && $value['mode'] !== null) ||
            (isset($value['title']) && !empty($value['mode']))
        ) {
            return $value;
        }

        return null;
    }

    //########################################

    protected function _renderOption($option, $value)
    {
        $value = isset($value['mode']) ? $value['mode'] : null;
        return parent::_renderOption($option, $value);
    }

    protected function _getHtmlName()
    {
        return "{$this->getColumn()->getId()}[mode]";
    }

    //########################################
}
