<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;

class Store extends AbstractForm
{
    protected function _prepareForm()
    {
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account')
            ? $this->getHelper('Data\GlobalData')->getValue('edit_account') : array();
        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $defaults = array(
            'ebay_store_title' => '',
            'ebay_store_url' => '',
            'ebay_store_subscription_level' => '',
            'ebay_store_description' => ''
        );

        $formData = array_merge($defaults, $formData);

        $formData['ebay_store_title'] = $this->getHelper('Data')->escapeHtml($formData['ebay_store_title']);
        $formData['ebay_store_subscription_level'] = $this->getHelper('Data')->escapeHtml(
            $formData['ebay_store_subscription_level']
        );
        $formData['ebay_store_description'] = $this->getHelper('Data')->escapeHtml($formData['ebay_store_description']);

        $isEdit = !!$this->getRequest()->getParam('id');

        if ($isEdit){
            $categoriesTreeArray = $account->getChildObject()->buildEbayStoreCategoriesTree();
        }

        $form = $this->_formFactory->create();

        $form->addField(
            'ebay_accounts_store',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
<p>This tab displays information about your eBay Store and might be helpful for the
Category settings via M2E Pro.</p><br>
<p>More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    , $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/MAItAQ'))
            ]
        );

        if (!is_null($formData['ebay_store_title']) && $formData['ebay_store_title'] != '') {

            $fieldset = $form->addFieldset(
                'information',
                [
                    'legend' => $this->__('Information'),
                    'collapsable' => false
                ]
            );

            $fieldset->addField(
                'store_title',
                'label',
                [
                    'label' => $this->__('Store Title'),
                    'value' =>  $formData['ebay_store_title']
                ]
            );

            $fieldset->addField(
                'url',
                'link',
                [
                    'label' => $this->__('URL'),
                    'value' =>  $formData['ebay_store_url'],
                    'href' => $formData['ebay_store_url'],
                    'target' => '_blank',
                    'class' => 'control-value external-link',
                ]
            );

            $fieldset->addField(
                'subscription_level',
                'label',
                [
                    'label' => $this->__('Subscription Level'),
                    'value' =>  $formData['ebay_store_subscription_level']
                ]
            );

            if (!empty($formData['ebay_store_description'])) {
                $fieldset->addField(
                    'description',
                    'label',
                    [
                        'label' => $this->__('Description'),
                        'value' =>  $formData['ebay_store_description']
                    ]
                );
            }

            $fieldset->addField(
                'refresh',
                'button',
                [
                    'label' => '',
                    'value' => $this->__('Refresh'),
                    'class' => 'action-primary',
                    'onclick' => 'EbayAccountObj.ebayStoreUpdate();',
                    'tooltip' => $this->__(
                        'Click on Refresh button to update eBay Store information - title, URL, Category tree, etc.'
                    )
                ]
            );

            $fieldset = $form->addFieldset(
                'categories',
                [
                    'legend' => $this->__('Categories'),
                    'collapsable' => true,
                    'tooltip' => $this->__(
                        '<p>The list below shows your eBay Store Category tree.
                        This data will be of help when you are looking for a particular Category ID which
                        then can be used for Category settings for your Products in M2E Pro Listings.</p>'
                    )
                ]
            );

            if (isset($categoriesTreeArray) && count($categoriesTreeArray) == 0) {

                $fieldset->addField(
                    'ebay_store_categories_not_found',
                    'label',
                    [
                        'label' => '',
                        'value' => $this->__('Categories not found.')
                    ]
                );

            } else {

                $hideButton = $this->createBlock('Magento\Button')->addData([
                    'label' => $this->__('Hide'),
                    'class' => 'primary',
                    'onclick' => 'EbayAccountObj.ebayStoreSelectCategoryHide();',
                    'style' => 'margin-left: 15px;'
                ]);

                $fieldset->addField(
                    'ebay_store_categories_selected',
                    'text',
                    [
                        'container_id' => 'ebay_store_categories_selected_container',
                        'label' => $this->__('Category ID'),
                        'name' => 'ebay_store_categories_selected',
                        'tooltip' => $this->__('Highlighted Category ID.'),
                        'readonly' => true,
                        'class' => 'hide',
                        'placeholder' => $this->__('Please, select the Category'),
                        'after_element_html' => $hideButton->toHtml()
                    ]
                );

                $fieldset->addField(
                    'tree-div',
                    self::CUSTOM_CONTAINER,
                    [
                        'label' => '',
                    ]
                );
            }

            if (!empty($categoriesTreeArray)) {

                $categoriesTreeArray = $this->getHelper('Data')->jsonEncode($categoriesTreeArray);

                $this->js->add(<<<JS
require([
    'M2ePro/Ebay/Account',
], function() {
    EbayAccountObj.ebayStoreSelectCategoryHide();
    EbayAccountObj.ebayStoreInitExtTree($categoriesTreeArray);
});
JS
                );
            }

        } else {

            $fieldset = $form->addFieldset(
                'no_subscription',
                [
                    'legend' => $this->__('Information'),
                    'collapsable' => false
                ]
            );

            $fieldset->addField(
                'no_subscription_message',
                'label',
                [
                    'value' => $this->__('This eBay Account does not have an eBay Store subscription.'),
                ]
            );

            $fieldset->addField(
                'refresh',
                'button',
                [
                    'value' => $this->__('Refresh'),
                    'class' => 'action-primary',
                    'onclick' => 'EbayAccountObj.ebayStoreUpdate();',
                ]
            );
        }

        $this->css->add(<<<CSS
#no_subscription .admin__field-control {
    margin-left: 20% !important;
}
CSS
);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}