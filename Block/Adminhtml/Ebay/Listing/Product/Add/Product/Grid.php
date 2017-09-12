<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Grid
{
    protected $visibility;
    protected $status;
    protected $websiteFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->visibility = $visibility;
        $this->status = $status;
        $this->websiteFactory = $websiteFactory;
        parent::__construct($magentoProductCollectionFactory, $type, $context, $backendHelper, $data);
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumnAfter('visibility', array(
            'header'    => $this->__('Visibility'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'visibility',
            'filter_index' => 'visibility',
            'options' => $this->visibility->getOptionArray()
        ),'qty');

        $this->addColumnAfter('status', array(
            'header'    => $this->__('Status'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'status',
            'filter_index' => 'status',
            'options' => $this->status->getOptionArray(),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ),'visibility');

        if (!$this->_storeManager->isSingleStoreMode()) {

            $this->addColumnAfter('websites', array(
                'header'    => $this->__('Websites'),
                'align'     => 'left',
                'width'     => '90px',
                'type'      => 'options',
                'sortable'  => false,
                'index'     => 'websites',
                'filter_index' => 'websites',
                'options'   => $this->websiteFactory->create()->getCollection()->toOptionHash(),
                'frame_callback' => array($this, 'callbackColumnWebsites')
            ),'status');
        }

        return parent::_prepareColumns();
    }

    //########################################

    protected function getSelectedProductsCallback()
    {
        return <<<JS
(function() {
    return function(callback) {
        return callback && callback({$this->getId()}_massactionJsObject.checkedString)
    }
})()
JS;

    }

    public function callbackColumnWebsites($value, $row, $column, $isExport)
    {
        if (is_null($value)) {
            $websites = [];
            foreach ($row->getWebsiteIds() as $websiteId) {
                $websites[] = $this->_storeManager->getWebsite($websiteId)->getName();
            }
            return implode(', ', $websites);
        }

        return $value;
    }

    //########################################
}