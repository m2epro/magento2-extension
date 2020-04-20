<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Range
 */
class Range extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Range
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        array $data = []
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;

        parent::__construct($context, $resourceHelper, $data);
    }

    //########################################
}
