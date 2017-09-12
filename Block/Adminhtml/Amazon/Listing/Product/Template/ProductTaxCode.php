<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template;

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