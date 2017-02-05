<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class JsPhpRenderer extends AbstractRenderer
{
    protected $jsPhp = [];

    public function addConstants($constants)
    {
        $this->jsPhp = array_merge($this->jsPhp, $constants);
        return $this;
    }

    public function render()
    {
        if (empty($this->jsPhp)) {
            return '';
        }

        $constants = $this->helperFactory->getObject('Data')->jsonEncode($this->jsPhp);

        return "M2ePro.php.add({$constants});";
    }

}