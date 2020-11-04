<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Create;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Create\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Transferring $transferring */
    protected $transferring;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Transferring $transferring,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->transferring = $transferring;

        parent::__construct($ebayFactory, $context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        $this->addCss('ebay/listing/templates.css');

        $step = (int)$this->getRequest()->getParam('step');

        switch ($step) {
            case 1:
                $this->stepOne();
                break;
            case 2:
                $this->stepTwo();
                break;
            case 3:
                $this->stepThree();
                break;
            case 4:
                $this->stepFour();
                if ($this->getRequest()->isPost() && $this->isCreationModeListingOnly()) {
                    // closing window for 3rd party products moving in new listing creation

                    return $this->getRawResult();
                }
                break;
            default:
                $this->clearSession();
                $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
                break;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('New Listing Creation'));
        $this->setPageHelpLink('x/WwItAQ');

        return $this->getResult();
    }

    //########################################

    private function stepOne()
    {
        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return $this->getResult();
        }

        $this->setWizardStep('listingAccount');

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            // clear session data if user came back to the first step and changed the marketplace
            // ---------------------------------------
            if ($this->getSessionValue('marketplace_id')
                && (int)$this->getSessionValue('marketplace_id') != (int)$post['marketplace_id']
            ) {
                $this->clearSession();
            }

            $this->setSessionValue('listing_title', strip_tags($post['title']));
            $this->setSessionValue('account_id', (int)$post['account_id']);
            $this->setSessionValue('marketplace_id', (int)$post['marketplace_id']);
            $this->setSessionValue('store_id', (int)$post['store_id']);

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 2]);

            return $this->getResult();
        }

        $listingOnlyMode = \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
        if ($this->getRequest()->getParam('creation_mode') == $listingOnlyMode) {
            $this->setSessionValue('creation_mode', $listingOnlyMode);
        }

        $this->addContent($this->createBlock('Ebay_Listing_Create_General'));
    }

    private function stepTwo()
    {
        if ($this->getSessionValue('account_id') === null ||
            $this->getSessionValue('marketplace_id') === null) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return $this->getResult();
        }

        $this->setWizardStep('listingGeneral');

        $templateNicks = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
        ];

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            foreach ($templateNicks as $nick) {
                $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

                $this->setSessionValue("template_id_{$nick}", $templateData['id']);
                $this->setSessionValue("template_mode_{$nick}", $templateData['mode']);
            }

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 3]);
            return $this->getResult();
        }

        $this->loadTemplatesDataFromSession();

        $data = [
            'allowed_tabs' => ['general']
        ];
        $content = $this->createBlock('Ebay_Listing_Edit');
        $content->setData($data);

        $this->addContent($content);

        return $this->getResult();
    }

    private function stepThree()
    {
        if ($this->getSessionValue('account_id') === null ||
            $this->getSessionValue('marketplace_id') === null) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return $this->getResult();
        }

        $this->setWizardStep('listingSelling');

        $templateNicks = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
        ];

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            foreach ($templateNicks as $nick) {
                $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

                $this->setSessionValue("template_id_{$nick}", $templateData['id']);
                $this->setSessionValue("template_mode_{$nick}", $templateData['mode']);
            }

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 4]);
            return $this->getResult();
        }

        $this->loadTemplatesDataFromSession();

        $data = [
            'allowed_tabs' => ['selling']
        ];
        $content = $this->createBlock('Ebay_Listing_Edit');
        $content->setData($data);

        $this->addContent($content);
        return $this->getResult();
    }

    private function stepFour()
    {
        if ($this->getSessionValue('account_id') === null ||
            $this->getSessionValue('marketplace_id') === null) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['step' => 1,'_current' => true]);
            return $this->getResult();
        }

        $this->setWizardStep('listingSynchronization');

        $templateNicks = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
        ];

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            foreach ($templateNicks as $nick) {
                // @codingStandardsIgnoreLine
                $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

                $this->setSessionValue("template_id_{$nick}", $templateData['id']);
                $this->setSessionValue("template_mode_{$nick}", $templateData['mode']);
            }

            $listing = $this->createListing();

            //todo Transferring move in another place?
            if ($listingId = $this->getRequest()->getParam('listing_id')) {
                $this->transferring->setListing(
                    $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId)
                );

                $this->clearSession();
                $this->transferring->setTargetListingId($listing->getId());

                return $this->_redirect(
                    '*/ebay_listing/transferring/index',
                    [
                        'listing_id' => $listingId,
                        'step'       => 3,
                    ]
                );
            }

            if ($this->isCreationModeListingOnly()) {
                // closing window for 3rd party products moving in new listing creation
                return $this->getRawResult()->setContents("<script>window.close();</script>");
            }

            $this->clearSession();

            if ((bool)$this->getRequest()->getParam('wizard', false)) {
                $this->setWizardStep('sourceMode');
                return $this->_redirect('*/wizard_installationEbay');
            }

            return $this->_redirect(
                '*/ebay_listing_product_add/sourceMode',
                [
                    'id' => $listing->getId(),
                    'listing_creation' => true
                ]
            );
        }

        $this->loadTemplatesDataFromSession();

        $data = [
            'allowed_tabs' => ['synchronization']
        ];
        $content = $this->createBlock('Ebay_Listing_Edit');
        $content->setData($data);

        $this->addContent($content);
        return $this->getResult();
    }

    //########################################

    private function createListing()
    {
        $data = [];
        $data['title'] = $this->getSessionValue('listing_title');
        $data['account_id'] = $this->getSessionValue('account_id');
        $data['marketplace_id'] = $this->getSessionValue('marketplace_id');
        $data['store_id'] = $this->getSessionValue('store_id');

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $data['marketplace_id']);

        $data['parts_compatibility_mode'] = null;
        if ($marketplace->getChildObject()->isMultiMotorsEnabled()) {
            $data['parts_compatibility_mode'] = \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_KTYPES;
        }

        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');

        foreach ($templateManager->getAllTemplates() as $nick) {
            $manager = $this->modelFactory->getObject('Ebay_Template_Manager')->setTemplate($nick);
            $templateId = $this->getSessionValue("template_id_{$nick}");
            $templateMode = $this->getSessionValue("template_mode_{$nick}");

            $idColumn = $manager->getIdColumnNameByMode($templateMode);
            $modeColumn = $manager->getModeColumnName();

            $data[$idColumn] = $templateId;
            $data[$modeColumn] = $templateMode;
        }

        $model = $this->ebayFactory->getObject('Listing');
        $model->addData($data);
        $model->save();

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($model->getComponentMode());
        $tempLog->addListingMessage(
            $model->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_LISTING,
            'Listing was Added',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE
        );

        return $model;
    }

    //########################################

    private function loadTemplatesDataFromSession()
    {
        $this->getHelper('Data_GlobalData')->setValue(
            'ebay_custom_template_title',
            $this->getSessionValue('listing_title')
        );

        $dataLoader = $this->getHelper('Component_Ebay_Template_Switcher_DataLoader');
        $dataLoader->load(
            $this->getHelper('Data_Session'),
            ['session_key' => \Ess\M2ePro\Model\Ebay\Listing::CREATE_LISTING_SESSION_DATA]
        );
    }

    //########################################

    protected function setSessionValue($key, $value)
    {
        $sessionData = $this->getSessionValue();
        $sessionData[$key] = $value;

        $this->getHelper('Data_Session')->setValue(
            \Ess\M2ePro\Model\Ebay\Listing::CREATE_LISTING_SESSION_DATA,
            $sessionData
        );

        return $this;
    }

    protected function getSessionValue($key = null)
    {
        $sessionData = $this->getHelper('Data_Session')->getValue(
            \Ess\M2ePro\Model\Ebay\Listing::CREATE_LISTING_SESSION_DATA
        );

        if ($sessionData === null) {
            $sessionData = [];
        }

        if ($key === null) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : null;
    }

    //########################################

    private function clearSession()
    {
        $this->getHelper('Data_Session')->setValue(
            \Ess\M2ePro\Model\Ebay\Listing::CREATE_LISTING_SESSION_DATA,
            null
        );
    }

    //########################################

    private function setWizardStep($step)
    {
        $wizardHelper = $this->getHelper('Module_Wizard');
        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK, $step);
    }

    //########################################

    private function isCreationModeListingOnly()
    {
        return $this->getSessionValue('creation_mode') === \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
    }

    //########################################
}
