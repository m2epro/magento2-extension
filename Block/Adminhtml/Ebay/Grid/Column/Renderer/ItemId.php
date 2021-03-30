<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

use \Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\ItemId
 */
class ItemId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
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
        $itemId = $this->_getValue($row);

        if ($row->getChildObject() && ($itemId === null || $itemId === '')) {
            $itemId = $row->getChildObject()->getData($this->getColumn()->getData('index'));
        }

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->getHelper('Module\Translation')->__('Not Listed') . '</span>';
        }

        if ($itemId === null || $itemId === '') {
            return $this->getHelper('Module\Translation')->__('N/A');
        }

        $accountId = ($this->getColumn()->getData('account_id')) ? $this->getColumn()->getData('account_id')
                                                                 : $row->getData('account_id');
        $marketplaceId = ($this->getColumn()->getData('marketplace_id')) ? $this->getColumn()->getData('marketplace_id')
                                                                         : $row->getData('marketplace_id');

        $url = $this->getUrl(
            '*/ebay_listing/gotoEbay/',
            [
                'item_id'        => $itemId,
                'account_id'     => $accountId,
                'marketplace_id' => $marketplaceId
            ]
        );

        return '<a href="' . $url . '" target="_blank">' . $itemId . '</a>';
    }

    //########################################
}
