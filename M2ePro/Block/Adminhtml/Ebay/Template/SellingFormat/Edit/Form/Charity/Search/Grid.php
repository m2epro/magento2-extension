<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Charity\Search;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Charity\Search\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $customCollectionFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->customCollectionFactory = $customCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayCharityGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $data = array_slice($this->getData('charities'), 0, 10);

        $collection = $this->customCollectionFactory->create();

        foreach ($data as $item) {
            $temp = [
                'id' => $item['id'],
                'name' => $item['name'],
            ];

            $collection->addItem(new \Magento\Framework\DataObject($temp));
        }

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header'        => $this->__('ID'),
            'width'         => '50px',
            'align'         => 'left',
            'type'          => 'text',
            'index'         => 'id',
            'escape'        => true,
            'sortable'      => false,
            'filter'        => false,
        ]);

        $this->addColumn('name', [
            'header'        => $this->__('Name'),
            'align'         => 'left',
            'type'          => 'text',
            'index'         => 'name',
            'escape'        => true,
            'sortable'      => false,
            'filter'        => false,
        ]);

        $this->addColumn('action', [
            'header'    => $this->__('Action'),
            'align'     => 'left',
            'width'     => '50px',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => false,
            'actions'   => [
                0 => [
                    'label' => $this->__('Select'),
                    'value' => 'selectNewCharity',
                ]
            ],
            'frame_callback' => [$this, 'callbackColumnActions']
        ]);

        return parent::_prepareColumns();
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $actions = $column->getActions();

        $id = $row->getData('id');
        $name = $row->getData('name');
        $name = $this->getHelper('Data')->escapeJs($name);

        $actions = reset($actions);

        $label = $actions['label'];
        $method = $actions['value'];
        $onclick = "EbayTemplateSellingFormatObj['{$method}']({$id}, '{$name}')";

        return <<<HTML
<div style="padding: 5px;">
    <a href="javascript:void(0)" onclick="{$onclick}">
    {$label}
    </a>
</div>
HTML;
    }

    public function getRowUrl($item)
    {
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/*', ['_current'=>true]);
    }

    //########################################
}
