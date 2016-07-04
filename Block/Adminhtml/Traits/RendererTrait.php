<?php

namespace Ess\M2ePro\Block\Adminhtml\Traits;

trait RendererTrait
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsPhpRenderer  */
    public $jsPhp;

    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsTranslatorRenderer  */
    public $jsTranslator;

    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsUrlRenderer  */
    public $jsUrl;

    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsRenderer  */
    public $js;

    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\CssRenderer  */
    public $css;
}