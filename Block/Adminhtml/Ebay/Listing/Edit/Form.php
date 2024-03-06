<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory */
    protected $listingCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Magento\Store */
    protected $storeHelper;
    protected $titles = [];

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Store $storeHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        array $data = []
    ) {
        $this->storeHelper = $storeHelper;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $url = $this->getUrl(
            '*/ebay_listing_edit/saveTitle',
            [
                'id' => $this->getRequest()->getParam('id'),
                '_current' => true,
                'titles' => $this->titles
            ]
        );

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'method' => 'post',
                    'action' => $url,
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );
        $this->getTitles();

        $titleFieldset = $form->addFieldset(
            'title',
            [
                'legend' => __('Title'),
                'collapsable' => false,
                'class' => 'fieldset-wide',
            ]
        );
        $titleFieldset->addField(
            'title_field',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'title' => __('Title'),
                'required' => true,
                'class' => 'validate-no-html-tags',
                'value' => $this->getListing()->getData('title'),
            ]
        );
        $titleFieldset->addField(
            'titles',
            'hidden',
            [
                'name' => 'titles',
                'value' => implode(',', $this->titles),
            ]
        );

        $storeViewFieldset = $form->addFieldset(
            'store_view',
            [
                'legend' => __('Store View'),
                'collapsable' => false,
                'class' => 'fieldset-wide',
            ]
        );

        $changeButton = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData([
            'label' => __('Change'),
            'class' => 'action-primary',
            'onclick' => 'EditListingStoreViewObj.openPopup()',
            'style' => 'margin-left: 15px;',
        ]);

        $storeViewFieldset->addField(
            'store_id',
            'text',
            [
                'name' => 'store_view',
                'label' => __('Store View'),
                'title' => __('Store View'),
                'value' => $this->storeHelper->getStorePath($this->getListing()->getStoreId()),
                'disabled' => true,
                'after_element_html' => $changeButton->toHtml(),
            ]
        );

        $this->jsUrl->add($this->getUrl('*/ebay_listing_edit/selectStoreView'), 'listing/selectStoreView');
        $this->jsUrl->add($this->getUrl('*/ebay_listing_edit/saveStoreView'), 'listing/saveStoreView');

        $this->jsTranslator->addTranslations([
            'Edit Listing Store View' => __('Edit Listing Store View'),
        ]);

        $this->js->add(
            <<<JS
    require([
         'M2ePro/Listing/EditStoreView'
    ], function(){

        window.EditListingStoreViewObj = new ListingEditListingStoreView('{$this->getListing()->getId()}');

    });
JS
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function getListing()
    {
        if ($this->listing === null && $this->getRequest()->getParam('id')) {
            $this->listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $this->getRequest()->getParam('id'));
        }

        return $this->listing;
    }

    protected function getTitles()
    {
        $collection = $this->listingCollectionFactory->create();
        $data = $collection->getData();
        foreach ($data as $row) {
            if ($row['title'] === $this->getListing()->getData('title')) {
                continue;
            }
            $this->titles[] = $row['title'];
        }
    }
}
