<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template;

use Magento\Framework\DB\Select;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    public const TEMPLATE_SELLING_FORMAT = 'selling_format';
    public const TEMPLATE_CATEGORY = 'category';
    public const TEMPLATE_SYNCHRONIZATION = 'synchronization';
    public const TEMPLATE_DESCRIPTION = 'description';

    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory */
    private $wrapperCollectionFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory $wrapperCollectionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory $wrapperCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        $this->wrapperCollectionFactory = $wrapperCollectionFactory;
        $this->walmartFactory = $walmartFactory;
        $this->resourceConnection = $resourceConnection;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('policy/grid.css');

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        // Prepare category collection
        // ---------------------------------------
        $collectionCategory = $this->activeRecordFactory->getObject('Walmart_Template_Category')->getCollection();
        $collectionCategory->getSelect()->reset(Select::COLUMNS);
        $collectionCategory->getSelect()->columns(
            [
                'id as template_id',
                'title',
                new \Zend_Db_Expr('\'' . self::TEMPLATE_CATEGORY . '\' as `type`'),
                'marketplace_id',
                'create_date',
                'update_date',
                'category_path',
                'browsenode_id',
            ]
        );
        // ---------------------------------------

        // Prepare selling format collection
        // ---------------------------------------
        $collectionSellingFormat = $this->walmartFactory->getObject('Template\SellingFormat')->getCollection();
        $collectionSellingFormat->getSelect()->reset(Select::COLUMNS);
        $collectionSellingFormat->getSelect()->columns(
            [
                'id as template_id',
                'title',
                new \Zend_Db_Expr('\'' . self::TEMPLATE_SELLING_FORMAT . '\' as `type`'),
                'second_table.marketplace_id',
                'create_date',
                'update_date',
                new \Zend_Db_Expr('NULL as `category_path`'),
                new \Zend_Db_Expr('NULL as `browsenode_id`'),
            ]
        );
        $collectionSellingFormat->getSelect()
                                ->where('component_mode = (?)', \Ess\M2ePro\Helper\Component\Walmart::NICK);
        // ---------------------------------------

        // Prepare synchronization collection
        // ---------------------------------------
        $collectionSynchronization = $this->activeRecordFactory->getObject('Template\Synchronization')->getCollection();
        $collectionSynchronization->getSelect()->reset(Select::COLUMNS);
        $collectionSynchronization->getSelect()->columns(
            [
                'id as template_id',
                'title',
                new \Zend_Db_Expr('\'' . self::TEMPLATE_SYNCHRONIZATION . '\' as `type`'),
                new \Zend_Db_Expr('\'0\' as `marketplace_id`'),
                'create_date',
                'update_date',
                new \Zend_Db_Expr('NULL as `category_path`'),
                new \Zend_Db_Expr('NULL as `browsenode_id`'),
            ]
        );
        $collectionSynchronization->getSelect()->where(
            'component_mode = (?)',
            \Ess\M2ePro\Helper\Component\Walmart::NICK
        );
        // ---------------------------------------

        // Prepare description collection
        // ---------------------------------------
        $collectionDescription = $this->walmartFactory->getObject('Template\Description')->getCollection();

        $collectionDescription->getSelect()->reset(Select::COLUMNS);
        $collectionDescription->getSelect()->columns(
            [
                'id as template_id',
                'title',
                new \Zend_Db_Expr('\'' . self::TEMPLATE_DESCRIPTION . '\' as `type`'),
                new \Zend_Db_Expr('\'0\' as `marketplace_id`'),
                'create_date',
                'update_date',
                new \Zend_Db_Expr('NULL as `category_path`'),
                new \Zend_Db_Expr('NULL as `browsenode_id`'),
            ]
        );
        // ---------------------------------------

        // Prepare union select
        // ---------------------------------------
        $collectionsArray = [
            $collectionCategory->getSelect(),
            $collectionSellingFormat->getSelect(),
            $collectionSynchronization->getSelect(),
            $collectionDescription->getSelect(),
        ];

        $unionSelect = $this->resourceConnection->getConnection()->select();
        $unionSelect->union($collectionsArray);
        // ---------------------------------------

        // Prepare result collection
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Collection\Wrapper $resultCollection */
        $resultCollection = $this->wrapperCollectionFactory->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            ['main_table' => $unionSelect],
            [
                'template_id',
                'title',
                'type',
                'marketplace_id',
                'create_date',
                'update_date',
                'category_path',
                'browsenode_id',
            ]
        );
        // ---------------------------------------

        //        echo $resultCollection->getSelectSql(true); exit;

        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header' => __('Details'),
            'align' => 'left',
            'type' => 'text',
            //            'width'         => '150px',
            'index' => 'title',
            'escape' => true,
            'filter_index' => 'main_table.title',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $options = [
            self::TEMPLATE_CATEGORY => __('Category'),
            self::TEMPLATE_SELLING_FORMAT => __('Selling'),
            self::TEMPLATE_DESCRIPTION => __('Description'),
            self::TEMPLATE_SYNCHRONIZATION => __('Synchronization'),
        ];
        $this->addColumn('type', [
            'header' => __('Type'),
            'align' => 'left',
            'type' => 'options',
            'width' => '120px',
            'sortable' => false,
            'index' => 'type',
            'filter_index' => 'main_table.type',
            'options' => $options,
        ]);

        $this->addColumn('marketplace', [
            'header' => __('Marketplace'),
            'align' => 'left',
            'type' => 'options',
            'width' => '100px',
            'index' => 'marketplace_id',
            'filter_index' => 'marketplace_id',
            'filter_condition_callback' => [$this, 'callbackFilterMarketplace'],
            'frame_callback' => [$this, 'callbackColumnMarketplace'],
            'options' => $this->getEnabledMarketplaceTitles(),
        ]);

        $this->addColumn('create_date', [
            'header' => __('Creation Date'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'datetime',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'filter_time' => true,
            'format' => \IntlDateFormatter::MEDIUM,
            'index' => 'create_date',
            'filter_index' => 'main_table.create_date',
        ]);

        $this->addColumn('update_date', [
            'header' => __('Update Date'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'datetime',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'filter_time' => true,
            'format' => \IntlDateFormatter::MEDIUM,
            'index' => 'update_date',
            'filter_index' => 'main_table.update_date',
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'action',
            'index' => 'actions',
            'filter' => false,
            'sortable' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'getter' => 'getTemplateId',
            'actions' => [
                [
                    'caption' => __('Edit'),
                    'url' => [
                        'base' => '*/walmart_template/edit',
                        'params' => [
                            'type' => '$type',
                        ],
                    ],
                    'field' => 'id',
                ],
                [
                    'caption' => __('Delete'),
                    'class' => 'action-default scalable add primary policy-delete-btn',
                    'url' => [
                        'base' => '*/walmart_template/delete',
                        'params' => [
                            'type' => '$type',
                        ],
                    ],
                    'field' => 'id',
                    'confirm' => __('Are you sure?'),
                ],
            ],
        ]);

        return parent::_prepareColumns();
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        if ($row->getData('type') != self::TEMPLATE_CATEGORY) {
            return $value;
        }

        $title = $this->dataHelper->escapeHtml($value);

        $categoryWord = __('Category');
        $categoryPath = !empty($row['category_path']) ? "{$row['category_path']} ({$row['browsenode_id']})"
            : __('Not Set');

        return <<<HTML
{$title}
<div>
    <span style="font-weight: bold">{$categoryWord}</span>: <span style="color: #505050">{$categoryPath}</span><br/>
</div>
HTML;
    }

    public function callbackColumnMarketplace($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            return __('Any');
        }

        return $value;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'category_path LIKE ? OR browsenode_id LIKE ? OR title LIKE ?',
            '%' . $value . '%'
        );
    }

    protected function callbackFilterMarketplace($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('marketplace_id = 0 OR marketplace_id = ?', (int)$value);
    }

    /**
     * @return array
     */
    private function getEnabledMarketplaceTitles(): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $collection */
        $collection = $this->marketplaceCollectionFactory->create();
        $collection->appendFilterEnabledMarketplaces(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->setOrder('title', 'ASC');

        return $collection->toOptionHash();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return $this->getUrl(
            '*/walmart_template/edit',
            [
                'id' => $item->getData('template_id'),
                'type' => $item->getData('type'),
                'back' => 1,
            ]
        );
    }
}
