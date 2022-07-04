<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;

class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->translationHelper = $translationHelper;
        $this->dataHelper = $dataHelper;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $this->_getValue($row);

        if (!$row->getData('is_variation_parent')) {
            if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">'.$this->translationHelper->__('Not Listed').'</span>';
            }

            if ($value === null || $value === '' ||
                ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
                    !$row->getData('is_online_price_invalid'))) {
                return $this->translationHelper->__('N/A');
            }

            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        $variationChildStatuses = $this->dataHelper->jsonDecode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses) || $value === null || $value === '') {
            return $this->translationHelper->__('N/A');
        }

        $activeChildrenCount = 0;
        foreach ($variationChildStatuses as $childStatus => $count) {
            if ($childStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
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
