<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search\Main
 */
class Main extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('amazon/listing/product/search/main.phtml');
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $data = [
            'id'    => 'productSearch_submit_button',
            'label' => $this->__('Search'),
            'class' => 'productSearch_submit_button submit action primary'
        ];
        $buttonSubmitBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('productSearch_submit_button', $buttonSubmitBlock);
        // ---------------------------------------

        parent::_beforeToHtml();
    }

    //########################################
}
