<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

use \Ess\M2ePro\Block\Adminhtml\Traits;

class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use Traits\BlockTrait;

    const ONLINE_QTY_SOLD      = 'online_qty_sold';
    const ONLINE_AVAILABLE_QTY = 'online_available_qty';

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->translationHelper = $translationHelper;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $this->_getValue($row);

        if ($row->getChildObject() && ($value === null || $value === '')) {
            $value = $row->getChildObject()->getData($this->getColumn()->getData('index'));
        }

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->translationHelper->__('Not Listed') . '</span>';
        }

        if ($value === null || $value === '') {
            return $this->translationHelper->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        $renderOnlineQty = ($this->getColumn()->getData('render_online_qty'))
                           ? $this->getColumn()->getData('render_online_qty')
                           : self::ONLINE_QTY_SOLD;

        if ($renderOnlineQty === self::ONLINE_AVAILABLE_QTY) {
            if ($row->getData('status') != \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                return '<span style="color: gray; text-decoration: line-through;">' . $value . '</span>';
            }
        }

        return $value;
    }

    //########################################
}
