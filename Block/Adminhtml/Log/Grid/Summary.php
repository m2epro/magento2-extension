<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Grid;

class Summary extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    const VIEW_LOG_LINK_SHOW = 0;
    const VIEW_LOG_LINK_HIDE = 1;

    protected $_template = 'log/grid/summary.phtml';
    protected $tip = NULL;
    protected $iconSrc = NULL;
    protected $rows = array();

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('logGridSummary');
        // ---------------------------------------
    }

    public function getTip()
    {
        return $this->tip;
    }

    public function getIconSrc()
    {
        return $this->iconSrc;
    }

    public function getEncodedRows()
    {
        return base64_encode(json_encode($this->rows));
    }

    public function getEntityId()
    {
        if (!isset($this->_data['entity_id']) || !is_int($this->_data['entity_id'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Entity ID is not set.');
        }

        return $this->_data['entity_id'];
    }

    public function getViewHelpHandler()
    {
        if (!isset($this->_data['view_help_handler']) || !is_string($this->_data['view_help_handler'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('View help handler is not set.');
        }

        return $this->_data['view_help_handler'];
    }

    public function getCloseHelpHandler()
    {
        if (!isset($this->_data['hide_help_handler']) || !is_string($this->_data['hide_help_handler'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Close help handler is not set.');
        }

        return $this->_data['hide_help_handler'];
    }

    public function getHideViewLogLink()
    {
        if (!empty($this->_data['hide_view_log_link'])) {
            return self::VIEW_LOG_LINK_HIDE;
        }
        return self::VIEW_LOG_LINK_SHOW;
    }

    protected function getRows()
    {
        if (!isset($this->_data['rows']) || !is_array($this->_data['rows'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Log rows are not set.');
        }

        if (count($this->_data['rows']) == 0) {
            return array();
        }

        return array_slice($this->_data['rows'], 0, 3);
    }

    protected function getTips()
    {
        if (!isset($this->_data['tips']) || !is_array($this->_data['tips'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Log tips are not set.');
        }

        return $this->_data['tips'];
    }

    protected function getIcons()
    {
        if (!isset($this->_data['icons']) || !is_array($this->_data['icons'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Log icons are not set.');
        }

        return $this->_data['icons'];
    }

    protected function _beforeToHtml()
    {
        $rows = $this->getRows();

        if (count($rows) == 0) {
            return parent::_beforeToHtml();
        }

        $lastActionRow = $rows[0];
        // ---------------------------------------

        // Get log icon
        // ---------------------------------------
        $icon = 'normal';
        $tip = $this->__('Last Action was completed successfully.');

        if (isset($lastActionRow['type'])) {
            $tip = $this->getTipByType($lastActionRow['type']);
            $icon = $this->getIconByType($lastActionRow['type']);
        }

        $this->tip = $this->getHelper('Data')->escapeHtml($tip);
        $this->iconSrc = $this->getViewFileUrl('Ess_M2ePro::images/log_statuses/'.$icon.'.png');
        $this->rows = $rows;
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    protected function getTipByType($type)
    {
        foreach ($this->getTips() as $tipType => $tip) {
            if ($tipType == $type) {
                return $this->__($tip);
            }
        }

        return $this->__('Last Action was completed successfully.');
    }

    protected function getIconByType($type)
    {
        foreach ($this->getIcons() as $iconType => $icon) {
            if ($iconType == $type) {
                return $icon;
            }
        }

        return 'normal';
    }

    protected function _toHtml()
    {
        if (count($this->rows) == 0) {
            return '';
        }

        return parent::_toHtml();
    }

    //########################################
}