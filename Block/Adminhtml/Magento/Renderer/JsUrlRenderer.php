<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class JsUrlRenderer extends AbstractRenderer
{
    protected $jsUrls = [];

    public function add($url, $alias = null)
    {
        if (is_null($alias)) {
            $alias = $url;
        }
        $this->jsUrls[$alias] = $url;

        return $this;
    }

    public function addUrls(array $urls)
    {
        $this->jsUrls = array_merge($this->jsUrls, $urls);
        return $this;
    }

    public function render()
    {
        if (empty($this->jsUrls)) {
            return '';
        }

        $urls = $this->helperFactory->getObject('Data')->jsonEncode($this->jsUrls);

        return "M2ePro.url.add({$urls});";
    }

}