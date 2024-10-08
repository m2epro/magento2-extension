<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

class ProductType extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    private \Ess\M2ePro\Model\Walmart\Account\Repository $walmartAccountRepository;
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Repository $walmartAccountRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->walmartAccountRepository = $walmartAccountRepository;
        $this->supportHelper = $supportHelper;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartProductType');
        $this->_controller = 'adminhtml_walmart_productType';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $this->buttonList->update('add', 'label', (string)__('Add Product Type'));
        $this->buttonList->update('add', 'onclick', '');

        $this->addButton('run_update_all', [
            'label' => (string)__('Refresh Walmart Data'),
            'onclick' => 'WalmartMarketplaceWithProductTypeSyncObj.updateAction()',
            'class' => 'save update_all_marketplace primary',
        ]);
    }

    protected function _prepareLayout()
    {
        $content = (string)__(
            'The page displays Walmart Product Types that are currently used in your M2E Pro Listings.<br/><br/>

            Here you can add a new Product Type, edit or delete existing ones.
            Learn how to manage walmart Product Types in
            <a href="%url" target="_blank" class="external-link">this article</a>.<br/><br/>
            To ensure that you have the most up-to-date Product Type information in your M2E Pro,
            simply click the <b>Refresh Walmart Data</b> button.
            This will synchronize any changes made to Product Types on Walmart. Whether certain specifics have been
            added or removed, you will see the updated information after the data is refreshed.',
            ['url' => $this->supportHelper->getDocumentationArticleUrl('walmart-product-type')]
        );

        $this->appendHelpBlock(['content' => $content]);
        $this->addButton(
            'add',
            [
                'label' => (string)__('Add Product Type'),
                'onclick' => sprintf("setLocation('%s')", $this->getUrl('*/walmart_productType/edit')),
                'class' => 'action-primary',
                'button_class' => '',
            ]
        );

        return parent::_prepareLayout();
    }

    public function _toHtml()
    {
        $this->jsUrl->addUrls([
            'walmart_marketplace_withProductType/runSynchNow' => $this->getUrl(
                '*/walmart_marketplace_withProductType/runSynchNow'
            ),
            'walmart_marketplace_withProductType/synchGetExecutingInfo' => $this->getUrl(
                '*/walmart_marketplace_withProductType/synchGetExecutingInfo'
            ),
        ]);

        $storedStatuses = [];
        foreach ($this->walmartAccountRepository->getAllItems() as $account) {
            $marketplace = $account->getChildObject()
                                   ->getMarketplace();
            $storedStatuses[] = [
                'marketplace_id' => $marketplace->getId(),
                'title' => $marketplace->getTitle(),
            ];
        }
        $storedStatuses = \Ess\M2ePro\Helper\Json::encode($storedStatuses);

        $syncLogUrl = $this->getUrl('*/walmart_synchronization_log/index');
        $this->jsTranslator->addTranslations([
            'marketplace_sync_success_message' => (string)__('Walmart Data Update was completed.'),
            'marketplace_sync_error_message' => (string)__(
                'Walmart Data Update was completed with errors.'
                . ' <a target="_blank" href="%url">View Log</a> for the details.',
                ['url' => $syncLogUrl]
            ),
            'marketplace_sync_warning_message' => (string)__(
                'Warning Data Update was completed with warnings.'
                . ' <a target="_blank" href="%url">View Log</a> for the details.',
                ['url' => $syncLogUrl]
            ),
        ]);

        $this->js->addOnReadyJs(
            <<<JS
            require([
                'M2ePro/Walmart/Marketplace/WithProductType/Sync',
                'M2ePro/Walmart/Marketplace/WithProductType/SyncProgress',
                'M2ePro/Plugin/ProgressBar',
                'M2ePro/Plugin/AreaWrapper'
            ], function() {
                const marketplaceProgress = new WalmartMarketplaceWithProductTypeSyncProgress(
                    new ProgressBar('product_type_progress_bar'),
                    new AreaWrapper('product_type_content_container')
                );
                window.WalmartMarketplaceWithProductTypeSyncObj = new WalmartMarketplaceWithProductTypeSync(
                    marketplaceProgress,
                    $storedStatuses
                );
            });
JS
        );

        return
            '<div id="product_type_progress_bar"></div>' .
            '<div id="product_type_content_container">' .
            parent::_toHtml() .
            '</div>';
    }
}
