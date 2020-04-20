<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Item\Product;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Mapping
 */
class Mapping extends AbstractContainer
{
    protected $_template = 'order/item/product/mapping.phtml';

    protected function _beforeToHtml()
    {
        $this->setChild(
            'product_mapping_grid',
            $this->createBlock('Order_Item_Product_Mapping_Grid')
        );

        $this->setChild(
            'product_mapping_help_block',
            $this->createBlock('HelpBlock')->setData([
                'content' => $this->__(
                    'As M2E Pro was not able to find appropriate Product in Magento Catalog,
                     you are supposed to find and map it manualy.
                     <br/><br/><b>Note:</b> Magento Order can be only created when all Products of
                     Order are found in Magento Catalog.'
                )
            ])
        );

        return parent::_beforeToHtml();
    }
}
