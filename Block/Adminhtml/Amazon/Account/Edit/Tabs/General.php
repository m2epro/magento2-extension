<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $accountBuilder;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;
        $this->amazonHelper = $amazonHelper;
        $this->supportHelper = $supportHelper;
        $this->accountBuilder = $accountBuilder;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');
        $accountData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($accountData['other_listings_mapping_settings'])) {
            $accountData['other_listings_mapping_settings'] = (array)\Ess\M2ePro\Helper\Json::decode(
                $accountData['other_listings_mapping_settings'],
                true
            );
        }

        $formData = array_merge(
            $this->accountBuilder->getDefaultData(),
            $accountData
        );

        $marketplacesCollection = $this->marketplaceCollectionFactory->create();
        $marketplacesCollection->addFieldToFilter('id', $formData['marketplace_id']);
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $marketplacesCollection->getFirstItem();

        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => __('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'class' => 'M2ePro-account-title',
                'label' => __('Title'),
                'required' => true,
                'value' => $formData['title'],
                'tooltip' => __('Title or Identifier of Amazon Account for your internal use.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'access_details',
            [
                'legend' => __('Access Details'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'marketplace_title',
            'note',
            [
                'label' => __('Marketplace'),
                'text' => $marketplace->getTitle(),
            ]
        );
        $fieldset->addField(
            'marketplace_id',
            'hidden',
            [
                'name' => 'marketplace_id',
                'value' => $formData['marketplace_id'],
            ]
        );

        $fieldset->addField(
            'merchant_id',
            'note',
            [
                'label' => __('Merchant ID'),
                'text' => $formData['merchant_id'],
                'tooltip' => __('Your Amazon Seller ID.'),
            ]
        );

        $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'label' => __('Update Access Data'),
                'onclick' => 'AmazonAccountObj.getToken(' . $formData['marketplace_id'] . ')',
                'class' => 'check M2ePro_check_button primary',
            ]
        );
        $fieldset->addField(
            'update_access_data_container',
            'label',
            [
                'label' => '',
                'after_element_html' => $button->toHtml(),
            ]
        );

        $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'label' => __('Check Token Validity'),
                'onclick' => 'AmazonAccountObj.checkClick()',
                'class' => 'check M2ePro_check_button primary',
                'id' => 'check_token_validity',
            ]
        );
        $fieldset->addField(
            'check_token_validity_container',
            'label',
            [
                'label' => '',
                'after_element_html' => $button->toHtml(),
            ]
        );

        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Account::class));
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon\Account', ['_current' => true]));
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/amazon_account/save',
                ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
            ),
            'checkAction' => $this->getUrl(
                '*/amazon_account/check',
                ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
            ),
            'deleteAction' => $this->getUrl(
                '*/amazon_account/delete',
                ['id' => $this->getRequest()->getParam('id')]
            ),
        ]);

        $this->jsTranslator->add('Please enter correct value.', __('Please enter correct value.'));

        $this->jsTranslator->add(
            'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
            . 'This will cause inappropriate work of all Accounts\' copies.',
            __(
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'
            )
        );

        $id = $this->getRequest()->getParam('id');
        $this->js->add("M2ePro.formData.id = '$id';");

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Account',
    ], function(){
        window.AmazonAccountObj = new AmazonAccount();
        AmazonAccountObj.initObservers();
    });
JS
        );

        $this->jsTranslator->addTranslations([
            'The specified Title is already used for other Account. Account Title must be unique.' => __(
                'The specified Title is already used for other Account. Account Title must be unique.'
            ),
        ]);

        $title = $this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']));
        $this->js->add("M2ePro.formData.title = '$title';");

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
