<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\General;

class SearchPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'amazon/template/product_type/search_popup.phtml';
    /** @var array */
    private $productTypes = [];

    /**
     * @param array $productTypes
     *
     * @return $this
     */
    public function setProductTypes(array $productTypes): self
    {
        $this->productTypes = $productTypes;

        return $this;
    }

    /**
     * @return array
     */
    public function getProductTypes(): array
    {
        return $this->productTypes;
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'product_type_configured',
            __(
                <<<HTML
<p>This Product Type is already configured in your M2E Pro. There's no need to go through the setup process again. If
you wish to review or adjust the settings, please click <a target="_blank" href="exist_product_type_url">here</a>.</p>
HTML
            )
        );

        return parent::_beforeToHtml();
    }
}
