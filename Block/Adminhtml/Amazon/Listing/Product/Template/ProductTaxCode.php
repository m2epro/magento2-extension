<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode
 */
class ProductTaxCode extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('amazon/listing/product/template/product_tax_code/main.phtml');
    }

    //########################################
}
