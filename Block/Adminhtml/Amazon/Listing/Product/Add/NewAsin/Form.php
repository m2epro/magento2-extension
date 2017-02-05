<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->listing = $this->parentFactory->getObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing', $this->getRequest()->getParam('id')
        );
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/descriptionTemplateAssignType', ['_current' => true]),
                'method' => 'post'
            ]]
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
                'value' => $this->__('Description Policy is required to Create New ASIN/ISBN'),
                'field_extra_attributes' =>
                    'id="categories_mode_block_title" style="font-weight: bold;font-size:18px;margin-bottom:0px"'
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
                'value' => $this->__('Below you can select the most convenient for you way to set Description Policy:'),
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
                        'label' => 'All Products same Description Policy'
                    ]
                ],
                'note' => <<<HTML
<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">
    {$this->__('New ASIN(s)/ISBN(s) will be created using the same Description Policy.')}
</div>
<div style="margin: 7px 52px">
    <b>{$this->__('Description Policy')}</b>:
    <span id="description_template_title" style="font-style: italic; color: #808080">{$this->__('Not selected')}</span>
    &nbsp;<a href="javascript:void(0);" id="edit_description_template">{$this->__('Edit')}</a>
    <input id="description_template_id" name="description_template_id" value="" type="hidden" />
    <input id="products_ids" name="products_ids" type="hidden" value="">
</div>
<label style="margin: 0 52px; display: none;" class="mage-error" id="same_description_template_error" >
    {$this->__('Please select Description Policy.')}
</label>
HTML
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
                        'label' => 'Based on Magento Categories'
                    ]
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__(
                        'Products will have Description Policies set according to the Magento Categories.'
                    ).'</div>'
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
                        'label' => 'Set Manually for each Product'
                    ]
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__('Set Description Policies for each Product (or a group of Products) manually.').'</div>'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    public function getProductsIds()
    {
        return $this->listing->getSetting('additional_data', 'adding_new_asin_listing_products_ids');
    }

    //########################################

    public function getDescriptionTemplateMode()
    {
        $listingAdditionalData = $this->listing->getData('additional_data');
        $listingAdditionalData = $this->getHelper('Data')->jsonDecode($listingAdditionalData);

        $mode = 'same';

        $sessionMode = $this->getHelper('Data\Session')->getValue('products_source');
        if ($sessionMode == 'category') {
            $mode = $sessionMode;
        }

        if (!empty($listingAdditionalData['new_asin_mode'])) {
            $mode = $listingAdditionalData['new_asin_mode'];
        }

        return $mode;
    }

    //########################################

    protected function _toHtml()
    {
        $productsIds = implode(',', $this->getProductsIds());

        $this->js->add(
<<<JS
    require([
        'Magento_Ui/js/modal/modal'
    ],function(modal) {

        $('mode1same').observe('change', function (e) {
            $('edit_description_template').show();
        });

        $('edit_form').observe('change', function(e) {
            if (e.target.tagName != 'INPUT') {
                return;
            }

            if (e.target.value != 'same') {
                $('edit_description_template').hide();
            } else {
                $('edit_description_template').show();
            }
        });

        createTemplateDescriptionInNewTab = function(stepWindowUrl) {
            var win = window.open(stepWindowUrl);

            var intervalId = setInterval(function(){
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                loadTemplateDescriptionGrid();
            }, 1000);
        };

        loadTemplateDescriptionGrid = function() {

            new Ajax.Request(
                '{$this->getUrl('*/amazon_listing_product_template_description/viewGrid'
            )}', {
                method: 'post',
                parameters: {
                    products_ids : filteredProductsIds,
                    check_is_new_asin_accepted : 1,
                    map_to_template_js_fn : 'selectTemplateDescription',
                    create_new_template_js_fn : 'createTemplateDescriptionInNewTab'
                },
                onSuccess: function (transport) {
                    $('template_description_grid').update(transport.responseText);
                    $('template_description_grid').show();
                }
            })
        };

        descriptionTemplateModeFormSubmit = function()
        {
            if ($('mode1same').checked && $('description_template_id').value == '') {
                $('same_description_template_error').show();
                return;
            }
            $('edit_form').submit();
        };

        selectTemplateDescription = function(el, templateId)
        {
            $('description_template_id').value = templateId;
            $('products_ids').value = filteredProductsIds;
            $('description_template_title').innerHTML = el.up('tr').down('td').down('a').innerHTML;
            $('same_description_template_error').hide();
            popup.modal('closeModal');
        };

        var modeElement = $$('input[value="{$this->getDescriptionTemplateMode()}"]').shift();

        modeElement.checked = true;
        if (modeElement.value != 'same') {
            $('edit_description_template').hide();
        } else {
            $('edit_description_template').show();
        }

        $('edit_description_template').observe('click', function(event) {

            var popupContent = '';
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

                    if (!$('template_description_pop_up_content')) {
                        $('html-body').insert({bottom: response.html});
                    }

                    popup = jQuery('#template_description_pop_up_content');

                    modal({
                        title: '{$this->__(
                                   'Please select the Description Policy for the process of New ASIN/ISBN creation'
                                 )}',
                        type: 'slide',
                        buttons: [{
                            text: '{$this->__('Add New Description Policy')}',
                            class: 'action primary ',
                            click: function () {
                                createTemplateDescriptionInNewTab(M2ePro.url.get('newTemplateDescriptionUrl'))
                            }
                        }]
                    }, popup);

                    popup.modal('openModal');

                    loadTemplateDescriptionGrid();
                }
            });

        });
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}