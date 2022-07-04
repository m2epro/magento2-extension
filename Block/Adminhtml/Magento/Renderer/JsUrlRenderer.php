<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsUrlRenderer
 */
class JsUrlRenderer extends AbstractRenderer
{
    protected $jsUrls = [];

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        parent::__construct($helperFactory);
        $this->dataHelper = $dataHelper;
    }

    public function add($url, $alias = null)
    {
        if ($alias === null) {
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

        $urls = $this->dataHelper->jsonEncode($this->jsUrls);

        return "M2ePro.url.add({$urls});";
    }
}
