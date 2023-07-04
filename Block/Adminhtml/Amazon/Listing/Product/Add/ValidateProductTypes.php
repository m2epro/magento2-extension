<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

class ValidateProductTypes extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;
    /** @var array */
    private $listingProductIds;

    public function __construct(
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $listingProductIds,
        array $data = []
    ) {
        $this->listing = $listing;
        parent::__construct($context, $data);
        $this->listingProductIds = $listingProductIds;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('validateProductTypes');
        $this->initToolbarButtons();
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('amazon/product_type_validation_grid.css');

        $layout = $this->getLayout();
        if (!$layout) {
            return parent::_prepareLayout();
        }

        $productTypeValidationGrid = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Amazon\ProductType\Validate\Grid::class,
            '',
            ['listingProductIds' => $this->listingProductIds]
        );

        $this->setChild('grid', $productTypeValidationGrid);
        $this->setChild('listing_header', $layout->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            'listing_header',
            ['data' => ['listing' => $this->listing]]
        ));

        $this->css->addFile('amazon/listing/view.css');

        $this->appendHelpBlock([
            'content' => \__('Data validation is to ensure accurate and complete information.
            The validation process only takes a few moments and will notify you if any
            Magento attributes need editing or do not meet Amazon requirements.'),
        ]);

        $this->js->add(<<<JS
    require([
        'M2ePro/Plugin/Storage'
    ], function(localStorage) {
        window.addEventListener('focus', function () {
            if (
                localStorage.get('is_need_revalidate_product_types')
                && typeof window['ProductTypeValidatorGridObj'] !== 'undefined'
            ) {
                localStorage.remove('is_need_revalidate_product_types');
                window['ProductTypeValidatorGridObj'].validateAll();
            }
        });
    });
JS
        );

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        return $this->getChildHtml('listing_header') . parent::getGridHtml();
    }

    private function initToolbarButtons(): void
    {
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $url = $this->getUrl('*/amazon_listing_product_add/index', [
            'id' => $this->getRequest()->getParam('id'),
            'step' => 4,
        ]);
        $this->addButton('back', [
            'label' => __('Back'),
            'onclick' => sprintf('setLocation("%s".replace(/#$/, ""));', $url),
            'class' => 'back',
        ]);

        $url = $this->getUrl(
            '*/amazon_listing_product_add/exitToListing',
            ['id' => $this->getRequest()->getParam('id')]
        );
        $confirm =
            '<strong>' . __('Are you sure?') . '</strong><br><br>'
            . __('All unsaved changes will be lost and you will be returned to the Listings grid.');
        $this->addButton(
            'exit_to_listing',
            [
                'label' => __('Cancel'),
                'onclick' => "confirmSetLocation('$confirm', '$url');",
                'class' => 'action-primary',
            ]
        );

        $url = $this->getUrl('*/*/index', [
            'id' => $this->getRequest()->getParam('id'),
            'step' => 6,
        ]);
        $this->addButton('add_products_search_asin_continue', [
            'label' => __('Continue'),
            'onclick' => 'setLocation("' . $url . '")',
            'class' => 'action-primary forward',
        ]);
    }
}
