<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class Variations extends AbstractContainer
{
    private \Ess\M2ePro\Model\Listing\Product $listingProduct;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->listingProduct = $listingProduct;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->removeButton('add');
    }

    protected function _toHtml()
    {
        $block = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => __(
                'In this Section you can find all Item Variations with possibility to search, filter,
                sort etc.<br/><br/>

                You can add eBay Catalog Identifiers for each Variation separately.
                In case the Value of eBay Catalog Identifier
                is provided here it will be used in priority over eBay Catalog Identifiers
                Settings of Description Policy.<br/><br/>

                For Bundle Products and Simple Products with Custom Options Settings for
                eBay Catalog Identifiers can be
                provided only for each Variation separately here, as Description Policy Settings cannot be applied
                for them.<br/><br/>

                <strong>Note:</strong> markers <strong>"will be added"</strong> and <strong>"will be deleted"</strong>
                mean that Variation will be Added/Removed during the next Revise Action.'
            ),
        ]);

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Plugin/Messages'
    ],function(MessageObj) {
            MessageObj.setContainer('#listing_product_variation_action_messages_container');
    });
JS
        );

        return
            $block->toHtml() .
            '<div id="listing_product_variation_action_messages_container"></div>' .
            parent::_toHtml();
    }

    protected function _prepareLayout()
    {
        $gridBlock = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\Tabs\Variations\Grid::class,
                '',
                ['listingProduct' => $this->listingProduct]
            );

        $this->setChild('grid', $gridBlock);

        return parent::_prepareLayout();
    }
}
