<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\TaxCodes;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\TaxCodes\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $customCollectionFactory;
    private $resourceConnection;

    private $marketplaceId;
    private $noSelection = false;

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

        $this->setId('walmartTemplateSellingFormatTaxCodesGrid');

        // Set default values
        //------------------------------
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        $this->setPagerVisibility(true);
        $this->setDefaultLimit(30);
        //------------------------------
    }

    public function setMarketplaceId($marketplaceId)
    {
        $this->marketplaceId = $marketplaceId;
    }

    public function setNoSelection($value)
    {
        $this->noSelection = $value;
        return $this;
    }

    //########################################

    protected function _prepareCollection()
    {
        $connRead = $this->resourceConnection->getConnection();

        $select = $connRead->select()
            ->from(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_walmart_dictionary_marketplace'),
                ['tax_codes']
            )
            ->where('marketplace_id = ?', $this->marketplaceId);

        $row = $select->query()->fetchColumn();

        $collection = $this->customCollectionFactory->create();
        foreach ($this->getHelper('Data')->jsonDecode($row) as $item) {
            $collection->addItem(new \Magento\Framework\DataObject($item));
        }

        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('tax_code', [
            'header'         => $this->__('Tax Code'),
            'align'          => 'left',
            'type'           => 'text',
            'index'          => 'tax_code',
            'width'          => '10px',
            'filter_condition_callback' => [$this, 'callbackFilterTaxCodes'],
            'sortable'       => false
        ]);

        $this->addColumn('description', [
            'header'         => $this->__('Description'),
            'align'          => 'left',
            'type'           => 'text',
            'index'          => 'description',
            'width'          => '645px',
            'filter_condition_callback'         => [$this, 'callbackFilterDescription'],
            'sortable'       => false
        ]);

        if (!$this->noSelection) {
            $this->addColumn('action', [
                'header'         => $this->__('Action'),
                'align'          => 'left',
                'type'           => 'text',
                'width'          => '115px',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => [$this, 'callbackColumnAction'],
            ]);
        }

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        $select = $this->__('Select');

        return <<<HTML
<a href="javascript:void(0)"
onclick="WalmartTemplateSellingFormatObj.taxCodePopupSelectAndClose({$row->getData('tax_code')});">
{$select}
</a>
HTML;
    }

    //########################################

    protected function callbackFilterTaxCodes($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $this->getCollection()->addFilter(
            'tax_code',
            $value,
            \Ess\M2ePro\Model\ResourceModel\Collection\Custom::CONDITION_LIKE
        );
    }

    protected function callbackFilterDescription($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $this->getCollection()->addFilter(
            'description',
            $value,
            \Ess\M2ePro\Model\ResourceModel\Collection\Custom::CONDITION_LIKE
        );
    }

    //########################################

    public function getRowUrl($item)
    {
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/getTaxCodesGrid', ['_current' => true]);
    }

    //########################################
}
