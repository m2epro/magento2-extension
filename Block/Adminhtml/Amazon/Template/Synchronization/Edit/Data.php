<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Data
 */
class Data extends AbstractBlock
{
    protected $_template = 'template/2_column.phtml';

    protected function _prepareLayout()
    {
        $this->setChild('tabs', $this->createBlock('Amazon_Template_Synchronization_Edit_Tabs'));

        $this->css->add(<<<CSS
.field-advanced_filter ul.rule-param-children {
    margin-top: 1em;
}
.field-advanced_filter .rule-param {
    vertical-align: top;
    display: inline-block;
}
.field-advanced_filter .rule-param .label {
    font-size: 14px;
    font-weight: 600;
}
CSS
        );

        return parent::_prepareLayout();
    }
}
