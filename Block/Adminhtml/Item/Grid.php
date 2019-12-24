<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Item;

use \Magento\Backend\Block\Widget\Grid\Extended as WidgetGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Item\Grid
 */
class Grid extends WidgetGrid
{

    /**
     * @var \Ess\M2ePro\Model\ItemFactory
     */
    protected $_itemFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Model\ItemFactory $itemFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $configModule,
        array $data = []
    ) {

        $configModule->setGroupValue('test', 'mode', 1);

        $this->_itemFactory = $itemFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('itemGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
//        $this->setVarNameFilter('item_filter');
    }

    // ########################################

    protected function _prepareCollection()
    {
        $collection = $this->_itemFactory->create()->getCollection();

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header' => 'ID',
            'type'   => 'number',
            'index'  => 'id',
            'width'  => '50px'
        ]);

        $this->addColumn('text', [
            'header'       => 'Text',
            'type'         => 'text',
            'index'        => 'text',
            'width'        => '350px',
        ]);

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('m2epro/*/grid', ['_current' => true]);
    }
}
