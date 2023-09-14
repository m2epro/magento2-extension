<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template;

class ProductType extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $amazonHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        array $data = []
    ) {
        $this->amazonHelper = $amazonHelper;
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateProductType');
        $this->_controller = 'adminhtml_amazon_template_productType';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $this->buttonList->update('add', 'label', $this->__('Add Product Type'));
        $this->buttonList->update('add', 'onclick', '');

        $this->addButton('run_update_all', [
            'label' => __('Refresh Amazon Data'),
            'onclick' => 'MarketplaceObj.updateAction()',
            'class' => 'save update_all_marketplace primary',
        ]);
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType|\Magento\Backend\Block\Widget\Grid\Container
     */
    protected function _prepareLayout()
    {
        $content = $this->__(
            'The page displays Amazon Product Types that are currently used in your M2E Pro Listings.<br/><br/>

            Here you can add a new Product Type, edit or delete existing ones.
            Learn how to manage Amazon Product Types in
            <a href="%url%" target="_blank" class="external-link">this article</a>.<br/><br/>
            To ensure that you have the most up-to-date Product Type information in your M2E Pro,
            simply click the <b>Refresh Amazon Data</b> button.
            This will synchronize any changes made to Product Types on Amazon. Whether certain specifics have been
            added or removed, you will see the updated information after the data is refreshed.',
            $this->supportHelper->getDocumentationArticleUrl('amazon-product-type')
        );

        $this->appendHelpBlock(
            [
                'content' => $content,
            ]
        );

        $url = $this->getUrl('*/amazon_template_productType/edit');
        $this->addButton(
            'add',
            [
                'label' => $this->__('Add Product Type'),
                'onclick' => 'setLocation(\'' . $url . '\')',
                'class' => 'action-primary',
                'button_class' => '',
            ]
        );

        return parent::_prepareLayout();
    }
    public function _toHtml()
    {
        $this->jsUrl->addUrls([
            'runSynchNow' => $this->getUrl('*/amazon_marketplace/runSynchNow'),
            'amazon_marketplace/synchGetExecutingInfo' => $this->getUrl(
                '*/amazon_marketplace/synchGetExecutingInfo'
            ),
            'logViewUrl' => $this->getUrl('*/amazon_synchronization_log/index'),
        ]);

        $storedStatuses = [];
        foreach ($this->amazonHelper->getMarketplacesListByActiveAccounts() as $marketplaceId => $marketplaceTitle) {
            $storedStatuses[] = [
                'marketplace_id' => $marketplaceId,
                'title' => $marketplaceTitle,
            ];
        }
        $storedStatuses = \Ess\M2ePro\Helper\Json::encode($storedStatuses);

        $this->js->addOnReadyJs(
            <<<JS
            require([
                'M2ePro/MarketplaceBuildUpdate',
                'M2ePro/Amazon/MarketplaceUpdateSynchProgress',
                'M2ePro/Plugin/ProgressBar',
                'M2ePro/Plugin/AreaWrapper'
            ], function() {
                window.MarketplaceProgressObj = new MarketplaceUpdateSynchProgress(
                    new ProgressBar('product_type_progress_bar'),
                    new AreaWrapper('product_type_content_container')
                );
                window.MarketplaceObj = new AmazonMarketplacesBuildUpdate(MarketplaceProgressObj, $storedStatuses);
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
