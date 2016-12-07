<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Feedback;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayFeedbackGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('buyer_feedback_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $accountId = $this->getRequest()->getParam('id');
        if (is_null($accountId)) {
            return parent::_prepareCollection();
        }

        $collection = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection();

        $dbExpr = new \Zend_Db_Expr('if(`main_table`.`seller_feedback_text` = \'\', 0, 1)');
        $collection->getSelect()
            ->joinLeft(
                array('mea' => $this->activeRecordFactory->getObject('Ebay\Account')->getResource()->getMainTable()),
                '(`mea`.`account_id` = `main_table`.`account_id`)',
                array('account_mode'=>'mode','have_seller_feedback' => $dbExpr)
            );

        $collection->addFieldToFilter('main_table.account_id', $accountId);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Add column filtering conditions to collection
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            $field = ( $column->getFilterIndex() ) ? $column->getFilterIndex() : $column->getIndex();
            if ($column->getFilterConditionCallback()) {
                call_user_func($column->getFilterConditionCallback(), $this->getCollection(), $column);
            } else {
                $cond = $column->getFilter()->getCondition();
                if ($field && isset($cond)) {
                    if ($field == 'have_seller_feedback') {
                        if ((int)$cond['eq'] == 0) {
                            $this->getCollection()->getSelect()->where('`main_table`.`seller_feedback_text` = \'\'');
                        } else if ((int)$cond['eq'] == 1) {
                            $this->getCollection()->getSelect()->where('`main_table`.`seller_feedback_text` != \'\'');
                        }
                    } else {
                        $this->getCollection()->addFieldToFilter($field , $cond);
                    }
                }
            }
        }
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('ebay_item_id', array(
            'header' => $this->__('Item ID'),
            'align'  => 'right',
            'type'   => 'text',
            'width'  => '50px',
            'index'  => 'ebay_item_id',
            'frame_callback' => array($this, 'callbackColumnEbayItemId')
        ));

        $this->addColumn('transaction_id', array(
            'header' => $this->__('Transaction ID'),
            'align'  => 'right',
            'type'   => 'text',
            'width'  => '105px',
            'index'  => 'ebay_transaction_id',
            'frame_callback' => array($this, 'callbackColumnTransactionId')
        ));

        $this->addColumn('buyer_feedback', array(
            'header' => $this->__('Buyer Feedback'),
            'width'  => '155px',
            'type'   => 'text',
            'index'  => 'buyer_feedback_text',
            'frame_callback' => array($this, 'callbackColumnBuyerFeedback')
        ));

        $this->addColumn('buyer_feedback_date', array(
            'header' => $this->__('Buyer Feedback Date'),
            'width'  => '155px',
            'type'   => 'datetime',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'  => 'buyer_feedback_date',
            'frame_callback' => array($this, 'callbackColumnBuyerFeedbackDate')
        ));

        $this->addColumn('seller_feedback', array(
            'header' => $this->__('Seller Feedback'),
            'width'  => '155px',
            'type'   => 'text',
            'index'  => 'seller_feedback_text',
            'frame_callback' => array($this, 'callbackColumnSellerFeedback')
        ));

        $this->addColumn('seller_feedback_date', array(
            'header' => $this->__('Seller Feedback Date'),
            'width'  => '155px',
            'type'   => 'datetime',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'  => 'seller_feedback_date',
            'frame_callback' => array($this, 'callbackColumnSellerFeedbackDate')
        ));

        $this->addColumn('buyer_feedback_type', array(
            'header'       => $this->__('Type'),
            'width'        => '50px',
            'align'        => 'center',
            'type'         => 'options',
            'filter_index' => 'buyer_feedback_type',
            'sortable'     => false,
            'options'      => array(
                'Neutral'  => $this->__('Neutral'),
                'Positive' => $this->__('Positive'),
                'Negative' => $this->__('Negative')
            ),
            'frame_callback' => array($this, 'callbackColumnFeedbackType'),
            'filter_condition_callback' => array($this, 'callbackFilterFeedbackType'),
        ));

        $this->addColumn('feedback_respond_status', array(
            'header'       => $this->__('Status'),
            'align'        => 'center',
            'type'         => 'options',
            'index'        => 'have_seller_feedback',
            'filter_index' => 'have_seller_feedback',
            'sortable'     => false,
            'options'      => array(
                0 => $this->__('Unresponded'),
                1 => $this->__('Responded')
            )
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnEbayItemId($value, $row, $column, $isExport)
    {
        $url = $this->getUrl('*/*/goToItem', array('feedback_id' => $row->getData('id')));

        return '<a href="'.$url.'" target="_blank">'
                . $this->getHelper('Data')->escapeHtml($value)
                . '</a>';
    }

    public function callbackColumnTransactionId($value, $row, $column, $isExport)
    {
        $value == 0 && $value = $this->__('No ID For Auction');
        $url = $this->getUrl('*/*/goToOrder/', array('feedback_id' => $row->getData('id')));

        return '<a href="'.$url.'" target="_blank">'.$this->getHelper('Data')->escapeHtml($value).'</a>';
    }

    public function callbackColumnBuyerFeedback($value, $row, $column, $isExport)
    {
        $feedbackType = $row->getData('buyer_feedback_type');

        switch ($feedbackType) {
            case \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE:
                $color = 'green';
                break;
            case \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEGATIVE:
                $color = 'red';
                break;
            default:
                $color = 'gray';
                break;
        }

        return "<span style=\"color: {$color};\">{$value}</span>";
    }

    public function callbackColumnBuyerFeedbackDate($value, $row, $column, $isExport)
    {
        if (strtotime($row->getData('buyer_feedback_date')) < strtotime('2001-01-02')) {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnSellerFeedback($value, $row, $column, $isExport)
    {
        if ($value) {
            return $value;
        } else {
            return <<<HTML
<a href="javascript:void(0);"
    onclick="EbayAccountGridObj.openSendResponsePopup(this,
        '{$row->getData('id')}',
        '{$row->getData('ebay_transaction_id')}',
        '{$row->getData('ebay_item_id')}',
        '{$this->getHelper('Data')->escapeJs($row->getData('buyer_feedback_text'))}');"
    >{$this->__('Send Response')}</a>
HTML;
        }
    }

    public function callbackColumnSellerFeedbackDate($value, $row, $column, $isExport)
    {
        if (strtotime($row->getData('seller_feedback_date')) < strtotime('2001-01-02')) {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnFeedbackType($value, $row, $column, $isExport)
    {
        $feedbackType = $row->getData('buyer_feedback_type');

        switch ($feedbackType) {
            case \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE:
                $feedbackTypeText = $this->__('Positive');
                $color = 'green';
                break;
            case \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEGATIVE:
                $feedbackTypeText = $this->__('Negative');
                $color = 'red';
                break;
            default:
                $feedbackTypeText = $this->__('Neutral');
                $color = 'gray';
                break;
        }

        return "<span style=\"color: {$color};\">{$feedbackTypeText}</span>";
    }

    //########################################

    public function callbackFilterFeedbackType($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        switch ($value) {
            case \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEGATIVE:
                $this->getCollection()->addFieldToFilter('buyer_feedback_type',
                    \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEGATIVE);
                break;
            case \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEUTRAL:
                $this->getCollection()->addFieldToFilter('buyer_feedback_type',
                    \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEUTRAL);
                break;
            case \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE:
                $this->getCollection()->addFieldToFilter('buyer_feedback_type',
                    \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE);
                break;
        }
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/getGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}