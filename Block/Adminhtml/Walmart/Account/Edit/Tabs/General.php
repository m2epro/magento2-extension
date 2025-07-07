<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

class General extends AbstractForm
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    protected $walmartFactory;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $walmartMarketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $WalmartFactory,
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $walmartMarketplaceRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->walmartFactory = $WalmartFactory;
        $this->walmartMarketplaceRepository = $walmartMarketplaceRepository;
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->walmartHelper = $walmartHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->jsTranslator->add(
            'confirmation_account_delete',
            __(
                <<<HTML
<p>You are about to delete your eBay/Amazon/Walmart seller account from M2E Pro. This will remove the
account-related Listings and Products from the extension and disconnect the synchronization.
Your listings on the channel will <b>not</b> be affected.</p>
<p>Please confirm if you would like to delete the account.</p>
<p>Note: once the account is no longer connected to your M2E Pro, please remember to delete it from
<a href="%1">M2E Accounts</a></p>
HTML
                ,
                $this->supportHelper->getAccountsUrl()
            )
        );
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');
        $formData = array_merge($account->getData(), $account->getChildObject()->getData());

        if (isset($formData['other_listings_mapping_settings'])) {
            $formData['other_listings_mapping_settings'] = (array)\Ess\M2ePro\Helper\Json::decode(
                $formData['other_listings_mapping_settings'],
                true
            );
        }

        $defaults = $this->modelFactory->getObject('Walmart_Account_Builder')->getDefaultData();

        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $content = $this->__(
            <<<HTML
<div>
    Under this section, you can link your Walmart account to M2E Pro.
    Read how to <a href="%url%" target="_blank">get the API credentials</a>.
</div>
HTML
            ,
            $this->supportHelper->getDocumentationArticleUrl('help/m2/walmart-integration/account-configurations')
        );

        $form->addField(
            'walmart_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $content,
            ]
        );

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
                'tooltip' => __('Title or Identifier of Walmart Account for your internal use.'),
            ]
        );

        $marketplaceId = (int)$formData['marketplace_id'];
        $marketplace = $this->walmartMarketplaceRepository->get($marketplaceId);
        $marketplacesValues[$marketplace->getId()] = $marketplace->getTitle();

        $fieldset->addField(
            'marketplace_id',
            self::SELECT,
            [
                'name' => 'marketplace_id',
                'label' => __('Marketplace'),
                'values' => $marketplacesValues,
                'value' => $formData['marketplace_id'],
                'disabled' => true,
                'required' => true,
            ]
        );

        $fieldset = $form->addFieldset(
            'access_details',
            [
                'legend' => __('Access Details'),
                'collapsable' => false,
            ]
        );

        if ($marketplaceId === \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA) {
            $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
                [
                    'label' => __('Update Access Data'),
                    'onclick' => 'WalmartAccountObj.openAccessDataPopup(\'' .
                        $this->getUrl(
                            '*/walmart_account_canada/updateCredentials',
                            ['id' => $this->getRequest()->getParam('id')],
                        ) . '\'
                )',
                    'class' => 'check m2epro_check_button primary',
                ],
            );

            $fieldset->addField(
                'update_access_data_container',
                'label',
                [
                    'label' => '',
                    'after_element_html' => $button->toHtml(),
                ],
            );
        } else {
            $url = $this->getUrl(
                '*/walmart_account_unitedStates/beforeGetToken',
                [
                    'id' => $this->getRequest()->getParam('id'),
                    '_current' => true,
                ]
            );

            $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
                [
                    'label' => __('Update Access Data'),
                    'onclick' => 'setLocation(\'' . $url . '\')',
                    'class' => 'check m2epro_check_button primary',
                ],
            );

            $fieldset->addField(
                'update_access_data_container',
                'label',
                [
                    'label' => '',
                    'after_element_html' => $button->toHtml(),
                ],
            );
        }

        $id = $this->getRequest()->getParam('id');
        $title = $this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']));

        $this->js->add("M2ePro.formData.id = '$id';");
        $this->js->add("M2ePro.formData.title = '$title';");

        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(WalmartAccount::class));
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(Walmart::class));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart\Account', ['_current' => true]));
        $this->jsUrl->addUrls(
            [
                'formSubmit' => $this->getUrl(
                    '*/walmart_account/save',
                    ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
                ),
                'deleteAction' => $this->getUrl(
                    '*/walmart_account/delete',
                    ['id' => $this->getRequest()->getParam('id')]
                ),
            ]
        );

        $this->jsTranslator->add(
            'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
            . 'This will cause inappropriate work of all Accounts\' copies.',
            __(
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'
            )
        );

        $this->jsTranslator->addTranslations(
            [
                'Coefficient is not valid.' => __(
                    'Coefficient is not valid.'
                ),
                'The specified Title is already used for other Account. Account Title must be unique.' => __(
                    'The specified Title is already used for other Account. Account Title must be unique.'
                ),
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Walmart/Account',
    ], function() {
        WalmartAccountObj.initValidation();
        WalmartAccountObj.initObservers();
    });
JS
            ,
            2
        );

        return parent::_prepareLayout();
    }
}
