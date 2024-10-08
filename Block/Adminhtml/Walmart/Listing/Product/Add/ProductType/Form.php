<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected ?\Ess\M2ePro\Model\Listing $listing = null;
    private \Ess\M2ePro\Helper\Data\Session $sessionDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        array $data = []
    ) {
        $this->sessionDataHelper = $sessionDataHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->listing = $this->parentFactory->getObjectLoaded(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Listing',
            $this->getRequest()->getParam('id')
        );
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/productTypeAssignType', ['_current' => true]),
                    'method' => 'post',
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'categories_mode',
            [
            ]
        );

        $fieldset->addField(
            'block-title',
            'label',
            [
                'value' => '',
                'field_extra_attributes' =>
                    'id="categories_mode_block_title" style="font-weight: bold;font-size:18px;margin-bottom:0px"',
            ]
        );
        $this->css->add(
            <<<CSS
    #categories_mode_block_title .admin__field-control{
        width: 90%;
    }
CSS
        );

        $fieldset->addField(
            'block-notice',
            'label',
            [
                'value' => __('Select the most convenient way to set the Product Type below:'),
                'field_extra_attributes' => 'style="margin-bottom: 0;"',
            ]
        );

        $fieldset->addField(
            'mode1',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => 'same',
                        'label' => (string)__('All Products same Product Type'),
                    ],
                ],
                'note' => <<<HTML
<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">
    {$this->__('All Products will have the same Product Type.')}
</div>
<div style="margin: 7px 52px">
    <b>{$this->__('Product Type')}</b>:
    <span id="product_type_title" style="font-style: italic; color: #808080">{$this->__('Not selected')}</span>
    &nbsp;<a href="javascript:void(0);" id="edit_product_type">{$this->__('Edit')}</a>
    <input id="product_type_id" name="product_type_id" value="" type="hidden" />
    <input id="products_ids" name="products_ids" type="hidden" value="">
</div>
<label style="margin: 0 52px; display: none;" class="mage-error" id="same_product_type_error" >
    {$this->__('Please select Product Type.')}
</label>
HTML
            ,
            ]
        );

        $fieldset->addField(
            'mode2',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => 'category',
                        'label' => 'Based on Magento Categories',
                    ],
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __(
                        'Product Type will be set for Products based on their Magento Categories.'
                    ) . '</div>',
            ]
        );

        $fieldset->addField(
            'mode3',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => 'manually',
                        'label' => 'Set Manually for each Product',
                    ],
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __('Allows you to set Product Type for each Product or a group of Products manually.')
                    . '</div>',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getProductsIds()
    {
        return $this->listing->getSetting('additional_data', 'adding_listing_products_ids');
    }

    public function getProductTypeMode()
    {
        $listingAdditionalData = $this->listing->getData('additional_data');
        $listingAdditionalData = \Ess\M2ePro\Helper\Json::decode($listingAdditionalData);

        $mode = 'same';

        $sessionMode = $this->sessionDataHelper->getValue('products_source');
        if ($sessionMode == 'category') {
            $mode = $sessionMode;
        }

        if (!empty($listingAdditionalData['product_type_mode'])) {
            $mode = $listingAdditionalData['product_type_mode'];
        }

        return $mode;
    }

    protected function _toHtml()
    {
        $productsIds = implode(',', $this->getProductsIds());

        $viewProductTypePopupUrl =
            $this->getUrl('*/walmart_listing_product_productType/ViewPopup');

        $this->js->add(
            <<<JS
    require([
        'Magento_Ui/js/modal/modal'
    ],function(modal) {

        $('mode1same').observe('change', function (e) {
            $('edit_product_type').show();
        });

        $('edit_form').observe('change', function(e) {
            if (e.target.tagName != 'INPUT') {
                return;
            }

            if (e.target.value != 'same') {
                $('edit_product_type').hide();
            } else {
                $('edit_product_type').show();
            }
        });

        createTemplateCategoryInNewTab = function(stepWindowUrl) {
            var win = window.open(stepWindowUrl);

            var intervalId = setInterval(function(){
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                loadTemplateCategoryGrid();
            }, 1000);
        };

        loadTemplateCategoryGrid = function() {

            new Ajax.Request(
                '{$this->getUrl(
                '*/walmart_listing_product_productType/viewGrid'
            )}', {
                method: 'post',
                parameters: {
                    products_ids : '{$productsIds}',
                    map_to_template_js_fn : 'selectTemplateCategory',
                    create_new_template_js_fn : 'createTemplateCategoryInNewTab'
                },
                onSuccess: function (transport) {
                    $('product_type_grid').update(transport.responseText);
                    $('product_type_grid').show();
                }
            })
        };

        categoryTemplateModeFormSubmit = function()
        {
            if ($('mode1same').checked && $('product_type_id').value == '') {
                $('same_product_type_error').show();
                return;
            }
            $('edit_form').submit();
        };

        selectTemplateCategory = function(el, templateId)
        {
            $('product_type_id').value = templateId;
            $('products_ids').value = '{$productsIds}';
            $('product_type_title').innerHTML = el.up('tr').down('td').down('a').innerHTML;
            $('same_product_type_error').hide();
            popup.modal('closeModal');
        };

        var modeElement = $$('input[value="{$this->getProductTypeMode()}"]').shift();

        modeElement.checked = true;
        if (modeElement.value != 'same') {
            $('edit_product_type').hide();
        } else {
            $('edit_product_type').show();
        }

        $('edit_product_type').observe('click', function(event) {

            new Ajax.Request('{$viewProductTypePopupUrl}', {
                method: 'post',
                parameters: {
                    products_ids : '{$productsIds}'
                },
                onSuccess: function (transport) {

                    if (!$('product_type_pop_up_content')) {
                        $('html-body').insert({bottom: transport.responseText});
                    }

                    popup = jQuery('#product_type_pop_up_content');

                    modal({
                        title: '{$this->__('Assign Product Type')}',
                        type: 'slide',
                        buttons: [{
                            text: '{$this->__('Add New Product Type')}',
                            class: 'action primary add_new_product_type',
                            click: function () {
                                createTemplateCategoryInNewTab(M2ePro.url.get('createProductTypeUrl'))
                            }
                        }]
                    }, popup);

                    popup.modal('openModal');

                    loadTemplateCategoryGrid();
                }
            });

        });
    });
JS
        );

        return parent::_toHtml();
    }
}
