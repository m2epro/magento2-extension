<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento;

/**
 * Class Button
 * @package Ess\M2ePro\Block\Adminhtml\Magento
 */
class Button extends \Magento\Backend\Block\Widget\Button
{
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->helperFactory = $helperFactory;

        parent::__construct($context, $data);
    }

    //########################################
}
