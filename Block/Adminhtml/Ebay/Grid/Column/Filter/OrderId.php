<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter;

class OrderId extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Text
{
    //########################################

    protected $helperFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        array $data = []
    )
    {
        $this->helperFactory = $helperFactory;

        parent::__construct($context, $resourceHelper, $data);
    }

    public function getHelper($helper, array $arguments = [])
    {
        return $this->helperFactory->getObject($helper, $arguments);
    }

    //########################################

    public function getValue($index=null)
    {
        return $this->getData('value', $index);
    }

    //########################################
}
