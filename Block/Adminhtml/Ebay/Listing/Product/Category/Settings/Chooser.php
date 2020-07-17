<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser
 */
class Chooser extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_marketplaceId;
    protected $_accountId;
    protected $_categoryMode;

    protected $_categoriesData = [];

    //########################################

    protected function _toHtml()
    {
        /** @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser */
        $chooserBlock = $this->createBlock('Ebay_Template_Category_Chooser');
        $chooserBlock->setAccountId($this->_accountId);
        $chooserBlock->setMarketplaceId($this->_marketplaceId);
        $chooserBlock->setCategoryMode($this->_categoryMode);
        $chooserBlock->setCategoriesData($this->_categoriesData);

        return <<<HTML
<div id="ebay_category_chooser" style="padding-top: 15px">
    {$chooserBlock->toHtml()}
</div>
<div style="clear: both"></div>
HTML;
    }

    //########################################

    public function setMarketplaceId($marketplaceId)
    {
        $this->_marketplaceId = $marketplaceId;
        return $this;
    }

    public function setAccountId($accountId)
    {
        $this->_accountId = $accountId;
        return $this;
    }

    public function setCategoriesData(array $data)
    {
        $this->_categoriesData = $data;
        return $this;
    }

    public function setCategoryMode($mode)
    {
        $this->_categoryMode = $mode;
        return $this;
    }

    //########################################
}
