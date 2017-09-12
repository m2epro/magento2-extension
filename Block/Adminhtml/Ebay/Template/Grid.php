<?php
namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;
use Magento\Framework\DB\Select;

class Grid extends AbstractGrid
{
    private $customCollectionFactory;
    private $resourceConnection;

    private $enabledMarketplacesCollection = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->customCollectionFactory = $customCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('policy/grid.css');

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayTemplateGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        // Prepare selling format collection
        // ---------------------------------------
        $collectionSellingFormat = $this->activeRecordFactory->getObject('Template\SellingFormat')
            ->getCollection();
        $collectionSellingFormat->getSelect()->join(
            array(
                'etsf' => $this->activeRecordFactory->getObject('Ebay\Template\SellingFormat')
                    ->getResource()->getMainTable()
            ),
            'main_table.id=etsf.template_selling_format_id',
            array('is_custom_template')
        );
        $collectionSellingFormat->getSelect()->reset(Select::COLUMNS);
        $collectionSellingFormat->getSelect()->columns(
            array('id as template_id', 'title', new \Zend_Db_Expr('\'0\' as `marketplace`'),
                new \Zend_Db_Expr('\''.\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT.'\' as `nick`'),
                'create_date', 'update_date')
        );
        $collectionSellingFormat->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $collectionSellingFormat->addFieldToFilter('is_custom_template', 0);
        // ---------------------------------------

        // Prepare synchronization collection
        // ---------------------------------------
        $collectionSynchronization = $this->activeRecordFactory->getObject('Template\Synchronization')->getCollection();
        $collectionSynchronization->getSelect()->join(
            array(
                'ets' => $this->activeRecordFactory->getObject('Ebay\Template\Synchronization')
                    ->getResource()->getMainTable()
            ),
            'main_table.id=ets.template_synchronization_id',
            array('is_custom_template')
        );
        $collectionSynchronization->getSelect()->reset(Select::COLUMNS);
        $collectionSynchronization->getSelect()->columns(
            array('id as template_id', 'title', new \Zend_Db_Expr('\'0\' as `marketplace`'),
                new \Zend_Db_Expr(
                    '\''.\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION.'\' as `nick`'
                ),
                'create_date', 'update_date')
        );
        $collectionSynchronization->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $collectionSynchronization->addFieldToFilter('is_custom_template', 0);
        // ---------------------------------------

        // Prepare description collection
        // ---------------------------------------
        $collectionDescription = $this->activeRecordFactory->getObject('Template\Description')->getCollection();
        $collectionDescription->getSelect()->join(
            array(
                'ets' => $this->activeRecordFactory->getObject('Ebay\Template\Description')
                    ->getResource()->getMainTable()
            ),
            'main_table.id=ets.template_description_id',
            array('is_custom_template')
        );
        $collectionDescription->getSelect()->reset(Select::COLUMNS);
        $collectionDescription->getSelect()->columns(
            array('id as template_id', 'title', new \Zend_Db_Expr('\'0\' as `marketplace`'),
                new \Zend_Db_Expr('\''.\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION.'\' as `nick`'),
                'create_date', 'update_date')
        );
        $collectionDescription->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $collectionDescription->addFieldToFilter('is_custom_template', 0);
        // ---------------------------------------

        // Prepare payment collection
        // ---------------------------------------
        $collectionPayment = $this->activeRecordFactory->getObject('Ebay\Template\Payment')->getCollection();
        $collectionPayment->getSelect()->reset(Select::COLUMNS);
        $collectionPayment->getSelect()->columns(
            array('id as template_id', 'title', 'marketplace_id as marketplace',
                new \Zend_Db_Expr('\''.\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT.'\' as `nick`'),
                'create_date', 'update_date')
        );
        $collectionPayment->addFieldToFilter('is_custom_template', 0);
        $collectionPayment->addFieldToFilter('marketplace_id', array('in' => $this->getEnabledMarketplacesIds()));
        // ---------------------------------------

        // Prepare shipping collection
        // ---------------------------------------
        $collectionShipping = $this->activeRecordFactory->getObject('Ebay\Template\Shipping')->getCollection();
        $collectionShipping->getSelect()->reset(Select::COLUMNS);
        $collectionShipping->getSelect()->columns(
            array('id as template_id', 'title', 'marketplace_id as marketplace',
                new \Zend_Db_Expr('\''.\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING.'\' as `nick`'),
                'create_date', 'update_date')
        );
        $collectionShipping->addFieldToFilter('is_custom_template', 0);
        $collectionShipping->addFieldToFilter('marketplace_id', array('in' => $this->getEnabledMarketplacesIds()));
        // ---------------------------------------

        // Prepare return collection
        // ---------------------------------------
        $collectionReturn = $this->activeRecordFactory->getObject('Ebay\Template\ReturnPolicy')->getCollection();
        $collectionReturn->getSelect()->reset(Select::COLUMNS);
        $collectionReturn->getSelect()->columns(
            array('id as template_id', 'title', 'marketplace_id as marketplace',
                new \Zend_Db_Expr('\''.\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY.'\' as `nick`'),
                'create_date', 'update_date')
        );
        $collectionReturn->addFieldToFilter('is_custom_template', 0);
        $collectionReturn->addFieldToFilter('marketplace_id', array('in' => $this->getEnabledMarketplacesIds()));
        // ---------------------------------------

        // Prepare union select
        // ---------------------------------------
        $unionSelect = $this->resourceConnection->getConnection()->select();
        $unionSelect->union(array(
            $collectionSellingFormat->getSelect(),
            $collectionSynchronization->getSelect(),
            $collectionDescription->getSelect(),
            $collectionPayment->getSelect(),
            $collectionShipping->getSelect(),
            $collectionReturn->getSelect()
        ));
        // ---------------------------------------

        // Prepare result collection
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Collection\Custom $resultCollection */
        $resultCollection = $this->customCollectionFactory->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            array('main_table' => $unionSelect),
            array('template_id', 'title', 'nick', 'marketplace', 'create_date', 'update_date')
        );
        // ---------------------------------------

        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', array(
            'header'        => $this->__('Title'),
            'align'         => 'left',
            'type'          => 'text',
//            'width'         => '150px',
            'index'         => 'title',
            'escape'        => true,
            'filter_index'  => 'main_table.title'
        ));

        $options = array(
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT => $this->__('Payment'),
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING => $this->__('Shipping'),
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY => $this->__('Return'),
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT
            => $this->__('Price, Quantity and Format'),
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION
            => $this->__('Description'),
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION
            => $this->__('Synchronization')
        );
        $this->addColumn('nick', array(
            'header'        => $this->__('Type'),
            'align'         => 'left',
            'type'          => 'options',
            'width'         => '100px',
            'sortable'      => false,
            'index'         => 'nick',
            'filter_index'  => 'main_table.nick',
            'options'       => $options
        ));

        $this->addColumn('marketplace', array(
            'header'        => $this->__('Marketplace'),
            'align'         => 'left',
            'type'          => 'options',
            'width'         => '100px',
            'index'         => 'marketplace',
            'filter_index'  => 'main_table.marketplace',
            'filter_condition_callback' => array($this, 'callbackFilterMarketplace'),
            'frame_callback'=> array($this, 'callbackColumnMarketplace'),
            'options'       => $this->getEnabledMarketplaceTitles()
        ));

        $this->addColumn('create_date', array(
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'filter_time' => true,
//            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage\Core\Model\Locale::FORMAT_TYPE_MEDIUM),
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ));

        $this->addColumn('update_date', array(
            'header'    => $this->__('Update Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'filter_time' => true,
//            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage\Core\Model\Locale::FORMAT_TYPE_MEDIUM),
            'index'     => 'update_date',
            'filter_index' => 'main_table.update_date'
        ));

        $this->addColumn('actions', array(
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'getter'    => 'getTemplateId',
            'actions'   => array(
                array(
                    'caption'   => $this->__('Delete'),
                    'class'     => 'action-default scalable add primary policy-delete-btn',
                    'url'       => array(
                        'base' => '*/ebay_template/delete',
                        'params' => array(
                            'nick' => '$nick'
                        )
                    ),
                    'field'    => 'id',
                    'confirm'  => $this->__('Are you sure?')
                )
            )
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnMarketplace($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            return $this->__('Any');
        }

        return $value;
    }

    protected function callbackFilterMarketplace($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.marketplace = 0 OR main_table.marketplace = ?', (int)$value);
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/templateGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/ebay_template/edit',
            array(
                'id' => $row->getData('template_id'),
                'nick' => $row->getData('nick'),
                'back' => 1
            )
        );
    }

    //########################################

    private function getEnabledMarketplacesCollection()
    {
        if (is_null($this->enabledMarketplacesCollection)) {
            $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
            $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
            $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
            $collection->setOrder('sorder', 'ASC');

            $this->enabledMarketplacesCollection = $collection;
        }

        return $this->enabledMarketplacesCollection;
    }

    private function getEnabledMarketplacesIds()
    {
        return $this->getEnabledMarketplacesCollection()->getAllIds();
    }

    private function getEnabledMarketplaceTitles()
    {
        return $this->getEnabledMarketplacesCollection()->toOptionHash();
    }

    //########################################
}