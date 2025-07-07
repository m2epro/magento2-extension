<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

class Content extends AbstractForm
{
    protected $walmartFactory;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    private \Ess\M2ePro\Block\Adminhtml\Walmart\Account\CredentialsFormFactory $credentialsFormFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Walmart\Account\CredentialsFormFactory $credentialsFormFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->supportHelper = $supportHelper;
        $this->walmartHelper = $walmartHelper;
        $this->dataHelper = $dataHelper;
        $this->credentialsFormFactory = $credentialsFormFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(
            $this->__(
                <<<HTML
<div>
    Under this section, you can link your Walmart account to M2E Pro.
    Read how to <a href="%url%" target="_blank">get the API credentials</a> or register on
    <a href="https://marketplace-apply.walmart.com/apply?id=00161000012XSxe" target="_blank">Walmart US</a> /
    <a href="https://marketplace.walmart.ca/apply?q=ca" target="_blank">Walmart CA</a>.
</div>
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('help/m2/walmart-integration/account-configurations')
            )
        );

        $marketplaceBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account\MarketplaceSelector::class
        );
        $this->setChild('marketplace_selector', $marketplaceBlock);

        parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        $form = $this->credentialsFormFactory->create(false, false, 'edit_form');
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(WalmartAccount::class));
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(Walmart::class));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Wizard\InstallationWalmart'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart\Account'));

        $checkAuthUrl = $this->getUrl('*/walmart_account/checkAuth');

        $this->js->addRequireJs(
            [
                'wa' => 'M2ePro/Walmart/Account',
            ],
            <<<JS
    WalmartAccountObj = new WalmartAccount();
WalmartAccountObj.initTokenValidation('{$checkAuthUrl}');
JS
        );

        $this->js->addOnReadyJs(
            <<<JS
    wait(
        function() {
            return typeof InstallationWalmartWizardObj != 'undefined';
        },
        function() {
            $('marketplace_id').simulate('change');
        },
        50
    );
JS
        );

        return parent::_beforeToHtml();
    }

    protected function _toHtml(): string
    {
        return $this->getChildHtml('marketplace_selector') . parent::_toHtml();
    }

    //########################################
}
