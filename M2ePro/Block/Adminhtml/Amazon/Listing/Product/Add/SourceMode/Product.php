<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Product
 */
class Product extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ListingAddFromProductList');
        $this->_controller = 'adminhtml_amazon_listing_product_add_sourceMode_product';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        if ($this->getRequest()->getParam('back') === null) {
            $url = $this->getUrl('*/amazon_listing_product_add/index', [
                'id' => $this->getRequest()->getParam('id'),
                'wizard' => $this->getRequest()->getParam('wizard')
            ]);
        } else {
            $url = $this->getHelper('Data')->getBackUrl(
                '*/amazon_listing/index'
            );
        }
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'ListingProductGridObj.backClick(\'' . $url . '\')',
            'class'     => 'back'
        ]);

        // ---------------------------------------
        $this->addButton('auto_action', [
            'label'     => $this->__('Auto Add/Remove Rules'),
            'onclick'   => 'ListingAutoActionObj.loadAutoActionHtml();',
            'class'     => 'action-primary'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('save_and_go_to_listing_view', [
            'label'     => $this->__('Continue'),
            'onclick'   => 'ListingProductGridObj.saveClick(\'view\')',
            'class'     => 'action-primary forward'
        ]);
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->createBlock(
            'Listing_View_Header',
            '',
            ['data' => ['listing' => $listing]]
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Amazon_Listing_AutoAction',
            ['id' => $this->getRequest()->getParam('id')]
        ));

        $path = 'amazon_listing_autoAction/getDescriptionTemplatesList';
        $this->jsUrl->add($this->getUrl('*/' . $path, [
            'marketplace_id' => $listing->getMarketplaceId(),
            'is_new_asin_accepted' => 1
        ]), $path);

        $this->jsTranslator->addTranslations([
            'Remove Category' => $this->__('Remove Category'),
            'Add New Group' => $this->__('Add New Group'),
            'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
            'Auto Add/Remove Rules' => $this->__('Auto Add/Remove Rules'),
            'Based on Magento Categories' => $this->__('Based on Magento Categories'),
            'You must select at least 1 Category.' => $this->__('You must select at least 1 Category.'),
            'Rule with the same Title already exists.' => $this->__('Rule with the same Title already exists.')
        ]);

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Amazon/Listing/AutoAction'
    ], function(){

        window.ListingAutoActionObj = new AmazonListingAutoAction();

    });
JS
        );

        $hideOthersListingsProductsFilterBlock = $this->createBlock(
            'Listing_Product_ShowOthersListingsProductsFilter'
        )->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller' => 'amazon_listing_product_add'
        ]);

        return $viewHeaderBlock->toHtml()
               . '<div class="filter_block">'
               . $hideOthersListingsProductsFilterBlock->toHtml()
               . '</div>'
               . parent::getGridHtml();
    }

    protected function _toHtml()
    {
        return '<div id="add_products_progress_bar"></div>'.
            '<div id="add_products_container">'.
            parent::_toHtml().
            '</div>';
    }

    //########################################
}
