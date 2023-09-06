<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionDataHelper;

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
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
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
                'value' => $this->__('Product Type is required to Create New ASIN/ISBN'),
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
                'value' => $this->__('Below you can select the most convenient for you way to set Product Type:'),
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
                        'label' => 'All Products same Product Type',
                    ],
                ],
                'note' => <<<HTML
<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">
    {$this->__('New ASIN(s)/ISBN(s) will be created using the same Product Type.')}
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
                    $this->__(
                        'Products will have Product Types set according to the Magento Categories.'
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
                    $this->__('Set Product Types for each Product (or a group of Products) manually.') . '</div>',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getProductsIds(): array
    {
        return $this
            ->listing
            ->getSetting('additional_data', 'adding_new_asin_listing_products_ids') ?? [];
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

        if (!empty($listingAdditionalData['new_asin_mode'])) {
            $mode = $listingAdditionalData['new_asin_mode'];
        }

        return $mode;
    }

    protected function _toHtml()
    {
        $productsIds = implode(',', $this->getProductsIds());

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

        createProductTypeInNewTab = function(stepWindowUrl) {
            var win = window.open(stepWindowUrl);

            var intervalId = setInterval(function(){
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                loadProductTypeGrid();
            }, 1000);
        };

        loadProductTypeGrid = function() {

            new Ajax.Request(
                '{$this->getUrl(
                '*/amazon_listing_product_template_productType/viewGrid'
            )}', {
                method: 'post',
                parameters: {
                    products_ids : filteredProductsIds,
                    check_is_new_asin_accepted : 1,
                    map_to_template_js_fn : 'selectProductType',
                    create_new_template_js_fn : 'createProductTypeInNewTab'
                },
                onSuccess: function (transport) {
                    $('product_type_grid').update(transport.responseText);
                    $('product_type_grid').show();
                }
            })
        };

        productTypeTemplateModeFormSubmit = function()
        {
            var productTypeId = $('product_type_id').value;

            if ($('mode1same').checked && productTypeId === '') {
                $('same_product_type_error').show();
                return;
            }

            $('edit_form').submit();
        };

        selectProductType = function(el, productTypeId)
        {
            $('product_type_id').value = productTypeId;
            $('products_ids').value = filteredProductsIds;
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

            new Ajax.Request('{$this->getUrl('*/amazon_listing_product/mapToNewAsin')}', {
                method: 'post',
                parameters: {
                    products_ids : '{$productsIds}'
                },
                onSuccess: function (transport) {
                    if (!transport.responseText.isJSON()) {
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    filteredProductsIds = response.products_ids;

                    if (!$('product_type_pop_up_content')) {
                        $('html-body').insert({bottom: response.html});
                    }

                    popup = jQuery('#product_type_pop_up_content');

                    modal({
                        title: '{$this->__(
                'Please select the Product Type for the process of New ASIN/ISBN creation'
            )}',
                        type: 'slide',
                        buttons: [{
                            text: '{$this->__('Add New Product Type')}',
                            class: 'action primary ',
                            click: function () {
                                createProductTypeInNewTab(M2ePro.url.get('createProductTypeUrl'))
                            }
                        }]
                    }, popup);

                    popup.modal('openModal');

                    loadProductTypeGrid();
                }
            });

        });
    });
JS
        );

        return parent::_toHtml();
    }
}
