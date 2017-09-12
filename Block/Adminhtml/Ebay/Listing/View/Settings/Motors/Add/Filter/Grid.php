<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Filter;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $motorsType;

    private $resourceConnection;

    //#########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

    //#########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayMotorAddTabFilterGrid');

        // Set default values
        //------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        //------------------------------
    }

    //------------------------------

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Filter\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay\Motor\Filter')->getCollection();
        $collection->addFieldToFilter('type', ['=' => $this->getMotorsType()]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'title',
            'filter_index' => 'title',
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);

        $this->addColumn('items', [
            'header'       => $this->getItemsColumnTitle(),
            'align'        => 'right',
            'type'         => 'text',
            'sortable'     => false,
            'filter'       => false,
            'index'        => 'conditions',
            'frame_callback' => [$this, 'callbackColumnItems']
        ]);

        $this->addColumn('note', [
            'header'       => $this->__('Note'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'note',
            'filter_index' => 'note'
        ]);

        $this->addColumn('conditions', [
            'header'       => $this->__('Conditions'),
            'align'        => 'left',
            'type'         => 'text',
            'sortable'     => false,
            'index'        => 'conditions',
            'filter_index' => 'conditions',
            'frame_callback' => [$this, 'callbackColumnConditions'],
            'filter_condition_callback' => [$this, 'callbackFilterConditions']
        ]);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('select', [
            'label'   => $this->__('Select'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('setNote', [
            'label'   => $this->__('Set Note'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('resetNote', [
            'label'   => $this->__('Reset Note'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('saveAsGroup', [
            'label'   => $this->__('Save As Group'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('removeFilter', [
            'label'   => $this->__('Remove'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);
        //--------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        return $value;
    }

    public function callbackColumnItems($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Motor\Filter $row */
        $conditions = $row->getConditions();
        $helper = $this->getHelper('Component\Ebay\Motors');

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($helper->getDictionaryTable($this->getMotorsType()));

        if ($helper->isTypeBasedOnEpids($this->getMotorsType())) {
            $select->where('scope = ?', $helper->getEpidsScopeByType($this->getMotorsType()));
        }

        foreach ($conditions as $key => $value) {

            if ($key != 'year') {
                $select->where('`'.$key.'` LIKE ?', '%'.$value.'%');
                continue;
            }

            if ($row->isTypeEpid()) {

                if (!empty($value['from'])) {
                    $select->where('`year` >= ?', $value['from']);
                }

                if (!empty($value['to'])) {
                    $select->where('`year` <= ?', $value['to']);
                }
            } else {

                if (!empty($value)) {
                    $select->where('from_year <= ?', $value);
                    $select->where('to_year >= ?', $value);
                }
            }
        }

        $itemsCount = $select->query()->rowCount();

        $applyWord = $this->__('apply');

        return <<<HTML
<script type="text/javascript">
    ebayMotorsFiltersConditions = typeof ebayMotorsFiltersConditions !== 'undefined' ? ebayMotorsFiltersConditions : {};
    ebayMotorsFiltersConditions[{$row->getId()}] = {$row->getConditions(false)};
</script>
{$itemsCount}
(<a onclick="EbayListingViewSettingsMotorsAddFilterGridObj.showFilterResult({$row->getId()})"
    href="javascript:void(0)">{$applyWord}</a>)
HTML;
    }

    public function callbackColumnConditions($value, $row, $column, $isExport)
    {
        $conditions = $this->getHelper('Data')->jsonDecode($row->getData('conditions'));

        if ($this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType())) {

            if (!empty($conditions['year'])) {
                $yearIndex = array_search("year",array_keys($conditions));

                !empty($conditions['year']['to']) && $conditions = array_merge(
                    array_slice($conditions, 0, $yearIndex),
                    ['Year To' => $conditions['year']['to']],
                    array_slice($conditions, $yearIndex)
                );

                !empty($conditions['year']['from']) && $conditions = array_merge(
                    array_slice($conditions, 0, $yearIndex),
                    ['Year From' => $conditions['year']['from']],
                    array_slice($conditions, $yearIndex)
                );

                unset($conditions['year']);
            }

            if (isset($conditions['product_type']) && $conditions['product_type'] != '') {

                switch($conditions['product_type']) {
                    case \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_VEHICLE:
                        $conditions['product_type'] = $this->__('Car / Truck');
                        break;

                    case \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_MOTORCYCLE:
                        $conditions['product_type'] = $this->__('Motorcycle');
                        break;

                    case \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_ATV:
                        $conditions['product_type'] = $this->__('ATV / Snowmobiles');
                        break;
                }
            }
        }

        $html = '<div class="product-options-main" style="font-size: 11px; color: grey; margin-left: 7px">';

        foreach ($conditions as $key => $value) {

            if ($key == 'epid') {
                $key = $this->getHelper('Data')->escapeHtml('ePID');
            } else if ($key == 'ktype') {
                $key = $this->getHelper('Data')->escapeHtml('kType');
            } else if ($key == 'body_style') {
                $key = $this->getHelper('Data')->escapeHtml('Body Style');
            } else if ($key == 'product_type') {
                $key = $this->getHelper('Data')->escapeHtml('Type');
            } else {
                $key = $this->getHelper('Data')->escapeHtml(ucfirst($key));
            }

            $value = $this->getHelper('Data')->escapeHtml($value);

            $html .= <<<HTML
<span class="attribute-row">
    <span class="attribute">
        <strong>{$key}</strong>:
    </span>
    <span class="value">{$value}</span>
</span>
<br/>
HTML;
        }

        $html .= '</div>';

        return $html;
    }

    //########################################

    protected function callbackFilterConditions($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where("conditions LIKE \"%{$value}%\"");
    }

    //########################################

    protected function _toHtml()
    {
        if (!$this->canDisplayContainer()) {

            $this->js->add(<<<JS
        EbayListingViewSettingsMotorsAddFilterGridObj.afterInitPage();
        EbayListingViewSettingsMotorsAddFilterGridObj
            .filtersConditions = typeof ebayMotorsFiltersConditions !== 'undefined' ? ebayMotorsFiltersConditions : {};
JS
            );

            return parent::_toHtml();
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Listing/View/Settings/Motors/Add/Filter/Grid'
    ], function() {

        EbayListingViewSettingsMotorsAddFilterGridObj = new EbayListingViewSettingsMotorsAddFilterGrid(
            '{$this->getId()}'
        );
        EbayListingViewSettingsMotorsAddFilterGridObj.afterInitPage();
        EbayListingViewSettingsMotorsAddFilterGridObj
            .filtersConditions = typeof ebayMotorsFiltersConditions !== 'undefined' ? ebayMotorsFiltersConditions : {};

    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_settings_motors/addFilterGrid', [
            '_current' => true
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
    }

    public function getMotorsType()
    {
        if (is_null($this->motorsType)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################

    public function getItemsColumnTitle()
    {
        if ($this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID(s)');
        }

        return $this->__('kType(s)');
    }

    //########################################
}