<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Model\Listing\Product;

class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory */
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
        if (!$value && $row->getChildObject()) {
            $value = $this->_getValue($row->getChildObject());
        }

        if (!$row->getData('is_variation_parent')) {
            if ($row->getData('status') == Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">' . $this->translationHelper->__('Not Listed') . '</span>';
            }

            if ($value === null || $value === '') {
                if ($row->getData('status') == Product::STATUS_BLOCKED) {
                    return $this->translationHelper->__('N/A');
                }

                return '<i style="color:gray;">receiving...</i>';
            }

            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        $variationChildStatuses = \Ess\M2ePro\Helper\Json::decode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses) || $value === null || $value === '') {
            return $this->translationHelper->__('N/A');
        }

        $activeChildrenCount = 0;
        foreach ($variationChildStatuses as $childStatus => $count) {
            if ($childStatus == Product::STATUS_NOT_LISTED) {
                continue;
            }

            $activeChildrenCount += (int)$count;
        }

        if ($activeChildrenCount == 0) {
            return $this->translationHelper->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    //########################################
}
