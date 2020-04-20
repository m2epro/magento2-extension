<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid;

use Magento\Backend\Block\Widget\Grid\Extended;
use Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
 */
abstract class AbstractGrid extends Extended
{
    use Traits\BlockTrait;
    use Traits\RendererTrait;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory;

    protected $_template = 'magento/grid/extended.phtml';

    protected $customPageSize = false;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->helperFactory = $context->getHelperFactory();
        $this->modelFactory = $context->getModelFactory();
        $this->activeRecordFactory = $context->getActiveRecordFactory();
        $this->parentFactory = $context->getParentFactory();

        $this->css = $context->getCss();
        $this->jsPhp = $context->getJsPhp();
        $this->js = $context->getJs();
        $this->jsTranslator = $context->getJsTranslator();
        $this->jsUrl = $context->getJsUrl();

        parent::__construct($context, $backendHelper, $data);
    }

    //####################################

    public function addColumn($columnId, $column)
    {
        if (is_array($column)) {
            if (!array_key_exists('header_css_class', $column)) {
                $column['header_css_class'] = 'grid-listing-column-' . $columnId;
            }

            if (!array_key_exists('column_css_class', $column)) {
                $column['column_css_class'] = 'grid-listing-column-' . $columnId;
            }
        }

        return parent::addColumn($columnId, $column);
    }

    //####################################

    public function getMassactionBlockName()
    {
        return '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Massaction';
    }

    //####################################

    public function isAllowedCustomPageSize()
    {
        return $this->customPageSize;
    }

    public function setCustomPageSize($value)
    {
        $this->customPageSize = $value;
        return $this;
    }

    //####################################
}
