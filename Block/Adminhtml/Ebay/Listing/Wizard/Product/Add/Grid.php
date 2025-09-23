<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\AbstractGrid;
use Ess\M2ePro\Helper\Magento\Product as ProductHelper;
use Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Listing as ListingResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product as ProductModel;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage as ListingRuntimeStorage;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;
use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Model\ResourceModel\Magento\Product\Filter\ExcludeSimpleProductsInVariation;
use Magento\Catalog\Model\Product\Type;
use Magento\Store\Model\WebsiteFactory;

class Grid extends AbstractGrid
{
    private \Magento\Store\Model\WebsiteFactory $websiteFactory;

    public function __construct(
        ExcludeSimpleProductsInVariation $excludeSimpleProductsInVariation,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        ListingRuntimeStorage $uiListingRuntimeStorage,
        ListingResource $listingResource,
        ProductModel $listingProductResource,
        CollectionFactory $magentoProductCollectionFactory,
        Type $type,
        ProductHelper $magentoProductHelper,
        WebsiteFactory $websiteFactory,
        Module $moduleHelper,
        Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->websiteFactory = $websiteFactory;

        parent::__construct(
            $excludeSimpleProductsInVariation,
            $uiWizardRuntimeStorage,
            $uiListingRuntimeStorage,
            $listingResource,
            $listingProductResource,
            $magentoProductCollectionFactory,
            $moduleHelper,
            $type,
            $magentoProductHelper,
            $context,
            $backendHelper,
            $dataHelper,
            $data,
        );
    }

    protected function _prepareColumns()
    {
        $this->addColumnAfter('visibility', [
            'header' => __('Visibility'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'visibility',
            'filter_index' => 'visibility',
            'options' => \Magento\Catalog\Model\Product\Visibility::getOptionArray(),
        ], 'qty');

        $this->addColumnAfter('status', [
            'header' => __('Status'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'status',
            'filter_index' => 'status',
            'options' => \Magento\Catalog\Model\Product\Attribute\Source\Status::getOptionArray(),
            'frame_callback' => [$this, 'callbackColumnStatus'],
        ], 'visibility');

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumnAfter('websites', [
                'header' => __('Websites'),
                'align' => 'left',
                'width' => '90px',
                'type' => 'options',
                'sortable' => false,
                'index' => 'websites',
                'filter_index' => 'websites',
                'options' => $this->websiteFactory->create()->getCollection()->toOptionHash(),
                'frame_callback' => [$this, 'callbackColumnWebsites'],
            ], 'status');
        }

        return parent::_prepareColumns();
    }

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

    public function callbackColumnWebsites($value, $row)
    {
        if ($value === null) {
            $websites = [];
            foreach ($row->getWebsiteIds() as $websiteId) {
                $websites[] = $this->_storeManager->getWebsite($websiteId)->getName();
            }

            return implode(', ', $websites);
        }

        return $value;
    }
}
