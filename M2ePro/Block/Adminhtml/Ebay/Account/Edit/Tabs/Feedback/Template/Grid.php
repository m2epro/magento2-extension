<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountEditTabsFeedbackGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $accountData = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        // Get collection of synchronizations
        $collection = $this->activeRecordFactory->getObject('Ebay_Feedback_Template')->getCollection()
            ->addFieldToFilter('main_table.account_id', $accountData->getId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('ft_title', [
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'body',
            'filter'    => false,
            'escape'    => true,
            'filter_index' => 'main_table.body',
            'frame_callback' => [$this, 'callbackBody']
        ]);

        $this->addColumn('ft_create_date', [
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'filter'    => false,
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ]);

        $this->addColumn('ft_update_date', [
            'header'    => $this->__('Update Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'filter'    => false,
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'update_date',
            'filter_index' => 'main_table.update_date'
        ]);

        $this->addColumn('ft_action_delete', [
            'header'    => $this->__('Delete'),
            'align'     => 'left',
            'width'     => '50px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'frame_callback' => [$this, 'callbackActionDelete']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackBody($value, $row, $column, $isExport)
    {
        return <<<HTML
{$value}
<div style="text-align: right;">
    <a href="javascript:void(0);"
        onclick="EbayAccountObj.openFeedbackTemplatePopup('{$row->getData('id')}');"
        >{$this->__('Edit')}</a>
</div>
HTML;
    }

    public function callbackActionDelete($value, $row, $column, $isExport)
    {
        return $this
            ->createBlock('Magento\Button')
            ->setData([
                'onclick' => 'EbayAccountObj.feedbacksDeleteAction(\''.$row->getData('id').'\');',
                'label' => $this->__('Delete'),
                'class' => 'action-default scalable delete icon-btn'
            ])->toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_account_feedback_template/getGrid', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function getEmptyText()
    {
        return '';
    }

    //########################################
}
