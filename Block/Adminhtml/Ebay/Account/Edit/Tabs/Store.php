<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Store extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $account = $this->globalDataHelper->getValue('edit_account')
            ? $this->globalDataHelper->getValue('edit_account') : [];
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $defaults = $this->modelFactory->getObject('Ebay_Account_Builder')->getDefaultData();

        $formData = array_merge($defaults, $formData);

        $formData['ebay_store_title'] = $this->dataHelper->escapeHtml($formData['ebay_store_title']);
        $formData['ebay_store_subscription_level'] = $this->dataHelper->escapeHtml(
            $formData['ebay_store_subscription_level']
        );
        $formData['ebay_store_description'] = $this->dataHelper->escapeHtml($formData['ebay_store_description']);

        $isEdit = !!$this->getRequest()->getParam('id');

        if ($isEdit) {
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
                    , $this->supportHelper->getDocumentationArticleUrl('x/WP8UB'))
            ]
        );

        if ($formData['ebay_store_title'] !== null && $formData['ebay_store_title'] != '') {
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
                    'onclick' => 'EbayAccountObj.refreshStoreCategories();',
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
                        'container_id' => 'ebay_store_categories_not_found',
                        'label' => '',
                        'value' => $this->__('Categories not found.')
                    ]
                );
            }

            if (!empty($categoriesTreeArray)) {
                $categoriesTreeArray = $this->dataHelper->jsonEncode($categoriesTreeArray);

                $this->js->add(<<<JS
require([
    'M2ePro/Ebay/Account',
], function() {
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
                    'container_id' => 'ebay_store_categories_no_subscription_message',
                    'value' => $this->__('This eBay Account does not have an eBay Store subscription.'),
                ]
            );

            $fieldset->addField(
                'refresh',
                'button',
                [
                    'value' => $this->__('Refresh'),
                    'class' => 'action-primary',
                    'onclick' => 'EbayAccountObj.refreshStoreCategories();',
                ]
            );
        }

        $hideButton = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData([
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

        $this->js->add(<<<JS
require([
    'M2ePro/Ebay/Account',
], function() {
    EbayAccountObj.ebayStoreSelectCategoryHide();
});
JS
        );

        $this->jsUrl->addUrls(
            [
                'ebay_account_store_category/refresh' => $this->getUrl(
                    '*/ebay_account_store_category/refresh/'
                ),
                'ebay_account_store_category/getTree' => $this->getUrl(
                    '*/ebay_account_store_category/getTree/'
                ),
            ]
        );

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
