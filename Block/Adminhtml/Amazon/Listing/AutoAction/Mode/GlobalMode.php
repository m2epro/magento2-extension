<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory as AmazonProductTypeCollectionFactory;

class GlobalMode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AbstractGlobalMode
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $amazonProductTypeCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        AmazonProductTypeCollectionFactory $amazonProductTypeCollectionFactory,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->amazonProductTypeCollectionFactory = $amazonProductTypeCollectionFactory;
        parent::__construct($context, $registry, $formFactory, $dataHelper, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $selectElementType = \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class;

        $form->addField(
            'global_mode_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '<p>These Rules of the automatic product adding and removal act globally for all Magento Catalog.
                    When a new Magento Product is added to Magento Catalog, it will be automatically added to the
                    current M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Please note if a product is already presented in another M2E Pro Listing with the related
                    Channel account and marketplace, the Item won’t be added to the Listing to prevent listing
                    duplicates on the Channel.</p><br>
                    <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is removed from Magento
                    Catalog, the Item will be removed from the Listing and its sale will be stopped on Channel.</p><br>
                    <p>More detailed information you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->supportHelper->getDocumentationArticleUrl('auto-add-remove-rules')
                ),
            ]
        );

        $form->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL,
            ]
        );

        $fieldSet = $form->addFieldset('auto_global_fieldset_container', []);

        if ($this->formData['auto_global_adding_mode'] == \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
            $fieldSet->addField(
                'auto_global_adding_mode',
                $selectElementType,
                [
                    'name' => 'auto_global_adding_mode',
                    'label' => __('New Product Added to Magento'),
                    'title' => __('New Product Added to Magento'),
                    'values' => [
                        ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE, 'label' => __('No Action')],
                        [
                            'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD,
                            'label' => __('Add to the Listing'),
                        ],
                    ],
                    'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
                    'tooltip' => __('Action which will be applied automatically.'),
                    'style' => 'width: 350px;',
                ]
            );
        } else {
            $fieldSet->addField(
                'auto_global_adding_mode',
                $selectElementType,
                [
                    'name' => 'auto_global_adding_mode',
                    'label' => __('New Product Added to Magento'),
                    'title' => __('New Product Added to Magento'),
                    'disabled' => true,
                    'values' => [
                        [
                            'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD,
                            'label' => __('Add to the Listing'),
                        ],
                    ],
                    'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD,
                    'tooltip' => __('Action which will be applied automatically.'),
                    'style' => 'width: 350px;',
                ]
            );
        }

        $fieldSet->addField(
            'auto_global_adding_add_not_visible',
            $selectElementType,
            [
                'name' => 'auto_global_adding_add_not_visible',
                'label' => $this->__('Add not Visible Individually Products'),
                'title' => $this->__('Add not Visible Individually Products'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO, 'label' => $this->__('No')],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                        'label' => $this->__('Yes'),
                    ],
                ],
                'value' => $this->formData['auto_global_adding_add_not_visible'],
                'field_extra_attributes' => 'id="auto_global_adding_add_not_visible_field"',
                'tooltip' => $this->__(
                    'Set to <strong>Yes</strong> if you want the Magento Products with
                    Visibility \'Not visible Individually\' to be added to the Listing
                    Automatically.<br/>
                    If set to <strong>No</strong>, only Variation (i.e.
                    Parent) Magento Products will be added to the Listing Automatically,
                    excluding Child Products.'
                ),
            ]
        );

        $fieldSet->addField(
            'auto_action_create_asin',
            self::SELECT,
            [
                'name' => 'auto_action_create_asin',
                'label' => $this->__('Create New ASIN / ISBN if not found'),
                'title' => $this->__('Create New ASIN / ISBN if not found'),
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO,
                        'label' => $this->__('No'),
                    ],
                    [
                        'value' => \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES,
                        'label' => $this->__('Yes'),
                    ],
                ],
                'value' => (int)!empty($this->formData['auto_global_adding_product_type_template_id']),
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_create_asin"',
                'tooltip' => $this->__(
                    'Should M2E Pro try to create new ASIN/ISBN in case Search
                    Settings are not set or contain the incorrect values?'
                ),
            ]
        );

        $collection = $this->amazonProductTypeCollectionFactory->create();
        $collection->appendFilterMarketplaceId($this->getListing()->getMarketplaceId());

        $productTypesTemplates = $collection->getData();

        $options = [['label' => '', 'value' => '', 'attrs' => ['class' => 'empty']]];
        foreach ($productTypesTemplates as $template) {
            $options[] = [
                'label' => $this->_escaper->escapeHtml($template['title']),
                'value' => $template['id']
            ];
        }

        $url = $this->getUrl('*/amazon_template_productType/edit', [
            'is_new_asin_accepted' => 1,
            'marketplace_id' => $this->getListing()->getMarketplaceId(),
            'close_on_save' => true,
        ]);

        $fieldSet->addField(
            'adding_product_type_template_id',
            self::SELECT,
            [
                'name' => 'adding_product_type_template_id',
                'label' => $this->__('Product Type'),
                'title' => $this->__('Product Type'),
                'values' => $options,
                'value' => $this->formData['auto_global_adding_product_type_template_id'],
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_assign_product_type_template"',
                'required' => true,
                'before_element_html' => <<<HTML
                    <span id="empty-product-type-message" style="display:inline-block;margin-top:7px;">
                    {$this->__('No product type available')}
                    </span>
HTML
                    ,
                'after_element_html' => $this->getTooltipHtml(
                    $this->__(
                        'Creation of new ASIN/ISBN will be performed based on specified Product Type.
                        Only the Product Types set for new ASIN/ISBN creation are available for choosing.
                        <br/><br/><b>Note:</b> If chosen Product Type doesn’t meet all the
                        Conditions for new ASIN/ISBN creation, the Products will still be added to M2E Pro Listings
                        but will not be Listed on Amazon.'
                    )
                ) . '<a href="javascript: void(0);"
                        style="vertical-align: inherit; margin-left: 65px;"
                        onclick="ListingAutoActionObj.addNewProductType(\'' . $url . '\',
                        ListingAutoActionObj.reloadProductTypeTemplates);">' . $this->__('Add New') . '
                     </a>',
            ]
        );

        $fieldSet->addField(
            'auto_global_deleting_mode',
            self::SELECT,
            [
                'name' => 'auto_global_deleting_mode',
                'disabled' => 'disabled',
                'label' => $this->__('Product Deleted from Magento'),
                'title' => $this->__('Product Deleted from Magento'),
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
                        'label' => $this->__('Stop on Channel and Delete from Listing'),
                    ],
                ],
                'style' => 'width: 350px;',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Listing::class)
        );

        $this->js->add(
            <<<JS

        $('auto_action_create_asin')
            .observe('change', ListingAutoActionObj.createAsinChange)
            .simulate('change');

        $('adding_product_type_template_id').observe('change', function(el) {
            var options = $(el.target).select('.empty');
            options.length > 0 && options[0].hide();
        });
JS
        );

        return parent::_afterToHtml($html);
    }

    //########################################
}
