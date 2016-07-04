<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class CssRenderer extends AbstractRenderer
{
    protected $css = [];
    protected $cssFiles = [];

    public function add($css)
    {
        $this->css[] = $css;
        return $this;
    }

    public function addFile($file)
    {
        $this->cssFiles[] = $file;
        return $this;
    }

    public function getFiles()
    {
        return $this->cssFiles;
    }

    public function render()
    {
        return implode($this->css);
    }
}