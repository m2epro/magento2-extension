<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class JsPhpRenderer extends AbstractRenderer
{
    protected $jsPhp = [];

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        parent::__construct($helperFactory);
        $this->dataHelper = $dataHelper;
    }

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

        $constants = $this->dataHelper->jsonEncode($this->jsPhp);

        return "M2ePro.php.add({$constants});";
    }
}
