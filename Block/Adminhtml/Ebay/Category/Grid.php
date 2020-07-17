<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category;

use \Ess\M2ePro\Model\Ebay\Template\Category as Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Category\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $ebayFactory;

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->setId('ebayCategoryGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    //########################################

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection();
        $collection->addFieldToFilter('category_mode', ['neq' => Template::CATEGORY_MODE_NONE]);
        $collection->addFieldToFilter('is_custom_template', 0);

        $collection->getSelect()->group(
            [
                'main_table.category_mode',
                'main_table.category_id',
                'main_table.category_attribute',
                'main_table.marketplace_id'
            ]
        );

        $collection->getSelect()->joinLeft(
            [
                'edc' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_ebay_dictionary_category')
            ],
            'edc.category_id = main_table.category_id AND edc.marketplace_id = main_table.marketplace_id',
            [
                'state' => new \Zend_Db_Expr('IF(edc.category_id, 1, 0)')
            ]
        );

        //----------------------------------------
        $connection = $this->resourceConnection->getConnection();

        $selectPrimary = $connection->select()
            ->from(
                ['elp' => $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable()],
                [new \Zend_Db_Expr('COUNT(listing_product_id)')]
            )->joinLeft(
                [
                    'etc1' =>
                        $this->activeRecordFactory->getObject('Ebay_Template_Category')->getResource()->getMainTable()
                ],
                'elp.template_category_id = etc1.id',
                []
            )
            ->where(
                str_replace(
                    ['%ali%', '%ebay_mode%'],
                    ['etc1', Template::CATEGORY_MODE_EBAY],
                    'IF(%ali%.category_mode = %ebay_mode%, %ali%.category_id, %ali%.category_attribute) = IF(
                        main_table.category_mode = %ebay_mode%, main_table.category_id, main_table.category_attribute
                    )'
                )
            )
            ->where('main_table.marketplace_id = etc1.marketplace_id');

        $selectSecondary = $connection->select()
            ->from(
                ['elp2' =>
                    $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable()],
                [new \Zend_Db_Expr('COUNT(listing_product_id)')]
            )
            ->joinLeft(
                [
                    'etc2' =>
                        $this->activeRecordFactory->getObject('Ebay_Template_Category')->getResource()->getMainTable()
                ],
                'elp2.template_category_secondary_id = etc2.id',
                []
            )
            ->where(
                str_replace(
                    ['%ali%', '%ebay_mode%'],
                    ['etc2', Template::CATEGORY_MODE_EBAY],
                    'IF(%ali%.category_mode = %ebay_mode%, %ali%.category_id, %ali%.category_attribute) = IF(
                        main_table.category_mode = %ebay_mode%, main_table.category_id, main_table.category_attribute
                    )'
                )
            )
            ->where('main_table.marketplace_id = etc2.marketplace_id');

        $collection->getSelect()->columns(
            [
                'template_category_id_count'           => $selectPrimary,
                'template_category_secondary_id_count' => $selectSecondary
            ]
        );

        //----------------------------------------

        $selectSpecificsTotal = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Ebay_Template_Category_Specific')->getResource()->getMainTable(),
                [new \Zend_Db_Expr('COUNT(id)')]
            )
            ->where('template_category_id = main_table.id');

        $collection->getSelect()->columns(
            ['template_category_specifics_count' => $selectSpecificsTotal]
        );

        $selectSpecificsUsed = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Ebay_Template_Category_Specific')->getResource()->getMainTable(),
                [new \Zend_Db_Expr('COUNT(id)')]
            )
            ->where('template_category_id = main_table.id')
            ->where('value_mode != ?', \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_NONE);

        $collection->getSelect()->columns(
            ['template_category_specifics_used_count' => $selectSpecificsUsed]
        );

        //----------------------------------------

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'path',
            [
                'header'         => $this->__('Title'),
                'align'          => 'left',
                'type'           => 'text',
                'escape'         => true,
                'index'          => 'main_table.category_path',
                'frame_callback' => [$this, 'callbackColumnPath'],
                'filter_condition_callback' => [$this, 'callbackFilterPath'],
            ]
        );

        $this->addColumn(
            'marketplace',
            [
                'header'        => $this->__('Marketplace'),
                'align'         => 'left',
                'type'          => 'options',
                'width'         => '150px',
                'index'         => 'marketplace_id',
                'filter_index'  => 'main_table.marketplace_id',
                'options'       => $this->ebayFactory->getObject('Marketplace')->getCollection()
                    ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                    ->setOrder('sorder', 'ASC')
                    ->toOptionHash()
            ]
        );

        $this->addColumn(
            'products_primary',
            [
                'header' => $this->__('Products: Primary'),
                'align'  => 'left',
                'type'   => 'text',
                'width'  => '100px',
                'index'  => 'template_category_id_count',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'products_secondary',
            [
                'header' => $this->__('Products: Secondary'),
                'align'  => 'left',
                'type'   => 'text',
                'width'  => '100px',
                'index'  => 'template_category_secondary_id_count',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'specifics_total',
            [
                'header' => $this->__('Specifics: Total'),
                'align'  => 'left',
                'type'   => 'text',
                'width'  => '100px',
                'index'  => 'template_category_specifics_count',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'specifics_used',
            [
                'header' => $this->__('Specifics: Used'),
                'align'  => 'left',
                'type'   => 'text',
                'width'  => '100px',
                'index'  => 'template_category_specifics_used_count',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'state',
            [
                'header'        => $this->__('State'),
                'align'         => 'left',
                'type'          => 'options',
                'index'         => 'state',
                'width'         => '150px',
                'sortable'      => false,
                'filter_condition_callback' => [$this, 'callbackFilterState'],
                'frame_callback'=> [$this, 'callbackColumnState'],
                'options'       => [
                    1 => $this->__('Active'),
                    0 => $this->__('Removed'),
                ],
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header'    => $this->__('Actions'),
                'align'     => 'left',
                'width'     => '70px',
                'type'      => 'action',
                'index'     => 'actions',
                'filter'    => false,
                'sortable'  => false,
                'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
                'getter'    => 'getTemplateId',
                'actions'   => [
                    [
                        'caption'   => $this->__('View'),
                        'url'       => [
                            'base' => '*/ebay_category/view',
                            'params' => [
                                'template_id' => '$id',
                            ]
                        ],
                        'field' => 'id'
                    ],
                ]
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label'   => $this->__('Remove'),
                'url'     => $this->getUrl('*/ebay_category/delete'),
                'confirm' => $this->__('Are you sure?')
            ]
        );

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnPath($value, $row, $column, $isExport)
    {
        $mode = $row->getData('category_mode');
        $value = $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
            $row->getData('category_id'),
            $row->getData('marketplace_id')
        );
        $value .= ' (' . $row->getData('category_id') . ')';

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $value = $this->__('Magento Attribute') .' > '.
                $this->getHelper('Magento\Attribute')->getAttributeLabel($row->getData('category_attribute'));
        }

        return $value;
    }

    public function callbackColumnState($value, $row, $column, $isExport)
    {
        if ($row->getData('category_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $row->setData('state', 1);
        }

        return $column->getRenderer()->render($row);
    }

    //########################################

    protected function callbackFilterPath($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'main_table.category_path LIKE ? OR main_table.category_id LIKE ? OR main_table.category_attribute LIKE ?',
            '%'. $value . '%'
        );
    }

    protected function callbackFilterState($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        if ($value == 1) {
            $collection->getSelect()->where(
                '(edc.category_id IS NOT NULL) OR
                (main_table.category_mode = '.\Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE.')'
            );
        } else {
            $collection->getSelect()->where(
                '(edc.category_id IS NULL) AND
                (main_table.category_mode != '.\Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE.')'
            );
        }
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    public function getRowClass($row)
    {
        if ($row->getData('category_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            return '';
        }

        return $row->getData('state') ? '' : 'invalid-row';
    }

    //########################################
}
