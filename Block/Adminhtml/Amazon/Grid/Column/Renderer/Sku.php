<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Sku
 */
class Sku extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $this->_getValue($row);

        if ((!$row->getData('is_variation_parent') &&
                $row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) ||
            ($row->getData('is_variation_parent') && $row->getData('general_id') == '')) {
            return '<span style="color: gray;">' . $this->getHelper('Module\Translation')->__('Not Listed') . '</span>';
        }

        if ($value === null || $value === '') {
            $value = $this->getHelper('Module\Translation')->__('N/A');
        }

        $showDefectedMessages = ($this->getColumn()->getData('show_defected_messages') !== null)
                                ? $this->getColumn()->getData('show_defected_messages')
                                : true;

        if (!$showDefectedMessages) {
            return $value;
        }

        if (!$row->getData('is_variation_parent') && $row->getData('defected_messages')) {
            $defectedMessages = $this->getHelper('Data')->jsonDecode($row->getData('defected_messages'));
            if (empty($defectedMessages)) {
                $defectedMessages = [];
            }

            $msg = '';
            foreach ($defectedMessages as $message) {
                if (empty($message['message'])) {
                    continue;
                }

                $msg .= '<p>'.$message['message'] . '&nbsp;';
                if (!empty($message['value'])) {
                    $msg .= $this->getHelper('Module\Translation')->__('Current Value') .': "' . $message['value'] .'"';
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

    //########################################
}
