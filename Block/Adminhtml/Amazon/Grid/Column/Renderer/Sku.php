<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer;

class Sku extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    public function render(\Magento\Framework\DataObject $row): string
    {
        return $this->renderGeneral($row, false);
    }

    public function renderExport(\Magento\Framework\DataObject $row): string
    {
        return $this->renderGeneral($row, true);
    }

    private function renderGeneral(\Magento\Framework\DataObject $row, bool $isExport)
    {
        $value = $this->_getValue($row);

        if (
            (!$row->getData('is_variation_parent') &&
                $row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) ||
            ($row->getData('is_variation_parent') && $row->getData('general_id') == '')
        ) {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        if ($value === null || $value === '') {
            if ($isExport) {
                return '';
            }

            $value = __('N/A');
        }

        if ($isExport) {
            return $value;
        }

        $showDefectedMessages = ($this->getColumn()->getData('show_defected_messages') !== null)
            ? $this->getColumn()->getData('show_defected_messages')
            : true;

        if (!$showDefectedMessages) {
            return $value;
        }

        if (!$row->getData('is_variation_parent') && $row->getData('defected_messages')) {
            $defectedMessages = \Ess\M2ePro\Helper\Json::decode($row->getData('defected_messages'));
            if (empty($defectedMessages)) {
                $defectedMessages = [];
            }

            $msg = '';
            foreach ($defectedMessages as $message) {
                if (empty($message['message'])) {
                    continue;
                }

                $msg .= '<p>' . $message['message'] . '&nbsp;';
                if (!empty($message['value'])) {
                    $msg .= __('Current Value') . ': "' . $message['value'] . '"';
                }
                $msg .= '</p>';
            }

            if (empty($msg)) {
                return $value;
            }

            $value .= <<<HTML
<span style="float:right;">
    <img id="map_link_defected_message_icon_{$row->getId()}"
         class="tool-tip-image"
         style="vertical-align: middle;"
         src="{$this->getViewFileUrl('Ess_M2ePro::images/warning.png')}">
    <span class="tool-tip-message tool-tip-warning tip-left" style="display:none;">
        <img src="{$this->getViewFileUrl('Ess_M2ePro::images/i_notice.gif')}">
        <span>{$msg}</span>
    </span>
</span>
HTML;
        }

        return $value;
    }
}
