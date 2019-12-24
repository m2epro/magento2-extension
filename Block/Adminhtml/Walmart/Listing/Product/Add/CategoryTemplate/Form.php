<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->listing = $this->parentFactory->getObjectLoaded(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Listing',
            $this->getRequest()->getParam('id')
        );
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/categoryTemplateAssignType', ['_current' => true]),
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
                'value' => '',
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
                'value' => $this->__('Select the most convenient way to set the Category Policy below:'),
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
                        'label' => 'All Products same Category Policy'
                    ]
                ],
                'note' => <<<HTML
<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">
    {$this->__('All Products will have the same Category Policy.')}
</div>
<div style="margin: 7px 52px">
    <b>{$this->__('Category Policy')}</b>:
    <span id="category_template_title" style="font-style: italic; color: #808080">{$this->__('Not selected')}</span>
    &nbsp;<a href="javascript:void(0);" id="edit_category_template">{$this->__('Edit')}</a>
    <input id="category_template_id" name="category_template_id" value="" type="hidden" />
    <input id="products_ids" name="products_ids" type="hidden" value="">
</div>
<label style="margin: 0 52px; display: none;" class="mage-error" id="same_category_template_error" >
    {$this->__('Please select Category Policy.')}
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
                        'Category Policy will be set for Products based on their Magento Categories.'
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
                    $this->__('Allows you to set Category Policy for each Product or a group of Products manually.')
                          .'</div>'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    public function getProductsIds()
    {
        return $this->listing->getSetting('additional_data', 'adding_listing_products_ids');
    }

    //########################################

    public function getCategoryTemplateMode()
    {
        $listingAdditionalData = $this->listing->getData('additional_data');
        $listingAdditionalData = $this->getHelper('Data')->jsonDecode($listingAdditionalData);

        $mode = 'same';

        $sessionMode = $this->getHelper('Data\Session')->getValue('products_source');
        if ($sessionMode == 'category') {
            $mode = $sessionMode;
        }

        if (!empty($listingAdditionalData['category_template_mode'])) {
            $mode = $listingAdditionalData['category_template_mode'];
        }

        return $mode;
    }

    //########################################

    protected function _toHtml()
    {
        $productsIds = implode(',', $this->getProductsIds());

        $viewTemplateCategoryPopupUrl =
            $this->getUrl('*/walmart_listing_product_template_category/ViewTemplateCategoryPopup');

        $this->js->add(
            <<<JS
    require([
        'Magento_Ui/js/modal/modal'
    ],function(modal) {

        $('mode1same').observe('change', function (e) {
            $('edit_category_template').show();
        });

        $('edit_form').observe('change', function(e) {
            if (e.target.tagName != 'INPUT') {
                return;
            }

            if (e.target.value != 'same') {
                $('edit_category_template').hide();
            } else {
                $('edit_category_template').show();
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
                '{$this->getUrl('*/walmart_listing_product_template_category/viewGrid'
            )}', {
                method: 'post',
                parameters: {
                    products_ids : '{$productsIds}',
                    map_to_template_js_fn : 'selectTemplateCategory',
                    create_new_template_js_fn : 'createTemplateCategoryInNewTab'
                },
                onSuccess: function (transport) {
                    $('template_category_grid').update(transport.responseText);
                    $('template_category_grid').show();
                }
            })
        };

        categoryTemplateModeFormSubmit = function()
        {
            if ($('mode1same').checked && $('category_template_id').value == '') {
                $('same_category_template_error').show();
                return;
            }
            $('edit_form').submit();
        };

        selectTemplateCategory = function(el, templateId)
        {
            $('category_template_id').value = templateId;
            $('products_ids').value = '{$productsIds}';
            $('category_template_title').innerHTML = el.up('tr').down('td').down('a').innerHTML;
            $('same_category_template_error').hide();
            popup.modal('closeModal');
        };

        var modeElement = $$('input[value="{$this->getCategoryTemplateMode()}"]').shift();

        modeElement.checked = true;
        if (modeElement.value != 'same') {
            $('edit_category_template').hide();
        } else {
            $('edit_category_template').show();
        }

        $('edit_category_template').observe('click', function(event) {

            var popupContent = '';
            new Ajax.Request('{$viewTemplateCategoryPopupUrl}', {
                method: 'post',
                parameters: {
                    products_ids : '{$productsIds}'
                },
                onSuccess: function (transport) {

                    if (!$('template_category_pop_up_content')) {
                        $('html-body').insert({bottom: transport.responseText});
                    }

                    popup = jQuery('#template_category_pop_up_content');

                    modal({
                        title: '{$this->__('Assign Category Policy')}',
                        type: 'slide',
                        buttons: [{
                            text: '{$this->__('Add New Category Policy')}',
                            class: 'action primary ',
                            click: function () {
                                createTemplateCategoryInNewTab(M2ePro.url.get('newTemplateCategoryUrl'))
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

    //########################################
}
