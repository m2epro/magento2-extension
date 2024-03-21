<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template;

use Magento\Framework\DB\Select;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    public const TEMPLATE_SELLING_FORMAT = 'selling_format';
    public const TEMPLATE_SYNCHRONIZATION = 'synchronization';
    public const TEMPLATE_SHIPPING = 'shipping';
    public const TEMPLATE_PRODUCT_TAX_CODE = 'product_tax_code';

    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory */
    private $wrapperCollectionFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Model\MarketplaceFactory */
    private $marketplaceFactory;

    public function __construct(
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory $wrapperCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->marketplaceFactory = $marketplaceFactory;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        $this->wrapperCollectionFactory = $wrapperCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('policy/grid.css');

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        // Prepare selling format collection
        // ---------------------------------------
        $collectionSellingFormat = $this->activeRecordFactory->getObject('Template\SellingFormat')->getCollection();
        $collectionSellingFormat->getSelect()->reset(Select::COLUMNS);
        $collectionSellingFormat->getSelect()->columns(
            [
                'id as template_id',
                'title',
                new \Zend_Db_Expr('\'' . self::TEMPLATE_SELLING_FORMAT . '\' as `type`'),
                new \Zend_Db_Expr('NULL as `marketplace_title`'),
                new \Zend_Db_Expr('\'0\' as `marketplace_id`'),
                'create_date',
                'update_date',
                new \Zend_Db_Expr('NULL as `category_path`'),
                new \Zend_Db_Expr('NULL as `browsenode_id`'),
                new \Zend_Db_Expr('NULL as `is_new_asin_accepted`'),
            ]
        );
        $collectionSellingFormat->getSelect()->where('component_mode = (?)', \Ess\M2ePro\Helper\Component\Amazon::NICK);
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
                new \Zend_Db_Expr('NULL as `marketplace_title`'),
                new \Zend_Db_Expr('\'0\' as `marketplace_id`'),
                'create_date',
                'update_date',
                new \Zend_Db_Expr('NULL as `category_path`'),
                new \Zend_Db_Expr('NULL as `browsenode_id`'),
                new \Zend_Db_Expr('NULL as `is_new_asin_accepted`'),
            ]
        );
        $collectionSynchronization->getSelect()->where(
            'component_mode = (?)',
            \Ess\M2ePro\Helper\Component\Amazon::NICK
        );
        // ---------------------------------------

        // Prepare Shipping collection
        // ---------------------------------------
        $collectionShipping = $this->activeRecordFactory->getObject('Amazon_Template_Shipping')
                                                        ->getCollection();
        $collectionShipping->getSelect()->join(
            ['mm' => $this->marketplaceFactory->create()->getResource()->getMainTable()],
            'main_table.marketplace_id=mm.id',
            []
        );
        $collectionShipping->addFieldToFilter('mm.status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        $collectionShipping->getSelect()->reset(Select::COLUMNS);
        $collectionShipping->getSelect()->columns(
            [
                'id as template_id',
                'title',
                new \Zend_Db_Expr('\'' . self::TEMPLATE_SHIPPING . '\' as `type`'),
                new \Zend_Db_Expr('mm.title as `marketplace_title`'),
                new \Zend_Db_Expr('mm.id as `marketplace_id`'),
                'create_date',
                'update_date',
                new \Zend_Db_Expr('NULL as `category_path`'),
                new \Zend_Db_Expr('NULL as `browsenode_id`'),
                new \Zend_Db_Expr('NULL as `is_new_asin_accepted`'),
            ]
        );
        $collectionShipping->addFieldToFilter('mm.status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        // ---------------------------------------

        // Prepare Product Tax Code collection
        // ---------------------------------------
        $collectionProductTaxCode = $this->activeRecordFactory->getObject('Amazon_Template_ProductTaxCode')
                                                              ->getCollection();

        $collectionProductTaxCode->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collectionProductTaxCode->getSelect()->columns(
            [
                'id as template_id',
                'title',
                new \Zend_Db_Expr('\'' . self::TEMPLATE_PRODUCT_TAX_CODE . '\' as `type`'),
                new \Zend_Db_Expr('NULL as `marketplace_title`'),
                new \Zend_Db_Expr('\'0\' as `marketplace_id`'),
                'create_date',
                'update_date',
                new \Zend_Db_Expr('NULL as `category_path`'),
                new \Zend_Db_Expr('NULL as `browsenode_id`'),
                new \Zend_Db_Expr('NULL as `is_new_asin_accepted`'),
            ]
        );
        // ---------------------------------------

        // Prepare union select
        // ---------------------------------------
        $collectionsArray = [
            $collectionSellingFormat->getSelect(),
            $collectionSynchronization->getSelect(),
            $collectionShipping->getSelect(),
            $collectionProductTaxCode->getSelect(),
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
                'marketplace_title',
                'marketplace_id',
                'create_date',
                'update_date',
                'category_path',
                'browsenode_id',
                'is_new_asin_accepted',
            ]
        );
        // ---------------------------------------

        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header' => $this->__('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'escape' => true,
            'filter_index' => 'main_table.title',
        ]);

        $options = [
            self::TEMPLATE_SELLING_FORMAT => $this->__('Selling'),
            self::TEMPLATE_SYNCHRONIZATION => $this->__('Synchronization'),
        ];
        $this->addColumn('type', [
            'header' => $this->__('Type'),
            'align' => 'left',
            'type' => 'options',
            'width' => '120px',
            'sortable' => false,
            'index' => 'type',
            'filter_index' => 'main_table.type',
            'options' => $options,
        ]);

        $this->addColumn('marketplace', [
            'header' => $this->__('Marketplace'),
            'align' => 'left',
            'type' => 'options',
            'width' => '100px',
            'index' => 'marketplace_title',
            'filter_index' => 'marketplace_title',
            'filter_condition_callback' => [$this, 'callbackFilterMarketplace'],
            'frame_callback' => [$this, 'callbackColumnMarketplace'],
            'options' => $this->getEnabledMarketplaceTitles(),
        ]);

        $this->addColumn('create_date', [
            'header' => $this->__('Creation Date'),
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
            'header' => $this->__('Update Date'),
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
            'header' => $this->__('Actions'),
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
                    'caption' => $this->__('Edit'),
                    'url' => [
                        'base' => '*/amazon_template/edit',
                        'params' => [
                            'type' => '$type',
                        ],
                    ],
                    'field' => 'id',
                ],
                [
                    'caption' => $this->__('Delete'),
                    'class' => 'action-default scalable add primary policy-delete-btn',
                    'url' => [
                        'base' => '*/amazon_template/delete',
                        'params' => [
                            'type' => '$type',
                        ],
                    ],
                    'field' => 'id',
                    'confirm' => $this->__('Are you sure?'),
                ],
            ],
        ]);

        parent::_prepareColumns();

        $options = [
            self::TEMPLATE_SELLING_FORMAT => $this->__('Selling'),
            self::TEMPLATE_SYNCHRONIZATION => $this->__('Synchronization'),
            self::TEMPLATE_SHIPPING => $this->__('Shipping'),
            self::TEMPLATE_PRODUCT_TAX_CODE => $this->__('Product Tax Code'),
        ];

        $this->getColumn('type')->setData('options', $options);

        $this->getColumn('title')->setData('header', $this->__('Title'));
        $this->getColumn('title')->setData('filter_condition_callback', [$this, 'callbackFilterTitle']);

        return $this;
    }

    public function callbackColumnMarketplace($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            return $this->__('Any');
        }

        return $value;
    }

    protected function callbackFilterTitle($collection, $column): void
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

    protected function callbackFilterMarketplace($collection, $column): void
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
        $collection->appendFilterEnabledMarketplaces(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->setOrder('title', 'ASC');

        return $collection->toOptionHash();
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    public function getRowUrl($item): string
    {
        return $this->getUrl(
            '*/amazon_template/edit',
            [
                'id' => $item->getData('template_id'),
                'type' => $item->getData('type'),
                'back' => 1,
            ]
        );
    }
}
