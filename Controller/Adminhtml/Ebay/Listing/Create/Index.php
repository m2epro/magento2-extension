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
    protected $sessionKey = 'ebay_listing_create';

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

        // ---------------------------------------
        if ($this->getRequest()->isPost()) {
            // save data
            $post = $this->getRequest()->getPost();

            // clear session data if user came back to the first step and changed the marketplace
            // ---------------------------------------
            if ($this->getSessionValue('marketplace_id')
                && (int)$this->getSessionValue('marketplace_id') != (int)$post['marketplace_id']
            ) {
                $this->clearSession();
            }
            // ---------------------------------------

            $this->setSessionValue('listing_title', strip_tags($post['title']));
            $this->setSessionValue('account_id', (int)$post['account_id']);
            $this->setSessionValue('marketplace_id', (int)$post['marketplace_id']);
            $this->setSessionValue('store_id', (int)$post['store_id']);

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 2]);

            return $this->getResult();
        }
        // ---------------------------------------
        $listingOnlyMode = \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
        if ($this->getRequest()->getParam('creation_mode') == $listingOnlyMode) {
            $this->setSessionValue('creation_mode', $listingOnlyMode);
        }

        $this->getHelper('Data\GlobalData')->setValue('ebay_listing_title', $this->getSessionValue('listing_title'));
        $this->getHelper('Data\GlobalData')->setValue('ebay_account_id', $this->getSessionValue('account_id'));
        $this->getHelper('Data\GlobalData')->setValue('ebay_marketplace_id', $this->getSessionValue('marketplace_id'));

        $this->addContent($this->createBlock('Ebay_Listing_Create_General'));
    }

    //########################################

    private function stepTwo()
    {
        // Check exist temp data
        // ---------------------------------------
        if ($this->getSessionValue('account_id') === null
            ||
            $this->getSessionValue('marketplace_id') === null
        ) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setWizardStep('listingGeneral');
        // ---------------------------------------

        $templateNicks = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
        ];

        // ---------------------------------------
        if ($this->getRequest()->isPost()) {
            // save data
            $post = $this->getRequest()->getPost();

            foreach ($templateNicks as $nick) {
                $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

                $this->setSessionValue("template_id_{$nick}", $templateData['id']);
                $this->setSessionValue("template_mode_{$nick}", $templateData['mode']);
            }

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 3]);
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->loadTemplatesDataFromSession();
        // ---------------------------------------

        // ---------------------------------------
        $data = [
            'allowed_tabs' => ['general']
        ];
        $content = $this->createBlock('Ebay_Listing_Edit');
        $content->setData($data);
        // ---------------------------------------

        $this->addContent($content);

        return $this->getResult();
    }

    //########################################

    private function stepThree()
    {
        // Check exist temp data
        // ---------------------------------------
        if ($this->getSessionValue('account_id') === null
            ||
            $this->getSessionValue('marketplace_id') === null
        ) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setWizardStep('listingSelling');
        // ---------------------------------------

        $templateNicks = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
        ];

        // ---------------------------------------
        if ($this->getRequest()->isPost()) {
            // save data
            $post = $this->getRequest()->getPost();

            foreach ($templateNicks as $nick) {
                // ---------------------------------------
                $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));
                // ---------------------------------------

                $this->setSessionValue("template_id_{$nick}", $templateData['id']);
                $this->setSessionValue("template_mode_{$nick}", $templateData['mode']);
            }

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 4]);
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->loadTemplatesDataFromSession();
        // ---------------------------------------

        // ---------------------------------------
        $data = [
            'allowed_tabs' => ['selling']
        ];
        $content = $this->createBlock('Ebay_Listing_Edit');
        $content->setData($data);
        // ---------------------------------------

        $this->addContent($content);
        return $this->getResult();
    }

    //########################################

    private function stepFour()
    {
        // Check exist temp data
        // ---------------------------------------
        if ($this->getSessionValue('account_id') === null
            ||
            $this->getSessionValue('marketplace_id') === null
        ) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['step' => 1,'_current' => true]);
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setWizardStep('listingSynchronization');
        // ---------------------------------------

        $templateNicks = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
        ];

        // ---------------------------------------
        if ($this->getRequest()->isPost()) {
            // save data
            $post = $this->getRequest()->getPost();

            foreach ($templateNicks as $nick) {
                $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

                $this->setSessionValue("template_id_{$nick}", $templateData['id']);
                $this->setSessionValue("template_mode_{$nick}", $templateData['mode']);
            }

            // ---------------------------------------
            $listing = $this->createListing();

            if ($this->isCreationModeListingOnly()) {
                // closing window for 3rd party products moving in new listing creation
                return $this->getRawResult()->setContents("<script>window.close();</script>");
            }

            $this->clearSession();
            // ---------------------------------------

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
        // ---------------------------------------

        // ---------------------------------------
        $this->loadTemplatesDataFromSession();
        // ---------------------------------------

        // ---------------------------------------
        $data = [
            'allowed_tabs' => ['synchronization']
        ];
        $content = $this->createBlock('Ebay_Listing_Edit');
        $content->setData($data);
        // ---------------------------------------

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

        // Set message to log
        // ---------------------------------------
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($model->getComponentMode());
        $tempLog->addListingMessage(
            $model->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_LISTING,
            // M2ePro_TRANSLATIONS
            // Listing was successfully Added
            'Listing was successfully Added',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
        // ---------------------------------------

        return $model;
    }

    //########################################

    private function loadTemplatesDataFromSession()
    {
        // ---------------------------------------
        $listingTitle = $this->getSessionValue('listing_title');
        $this->getHelper('Data\GlobalData')->setValue('ebay_custom_template_title', $listingTitle);

        $dataLoader = $this->getHelper('Component_Ebay_Template_Switcher_DataLoader');
        $dataLoader->load($this->getHelper('Data\Session'), ['session_key' => $this->sessionKey]);
        // ---------------------------------------
    }

    //########################################

    protected function setSessionValue($key, $value)
    {
        $sessionData = $this->getSessionValue();
        $sessionData[$key] = $value;

        $this->getHelper('Data\Session')->setValue($this->sessionKey, $sessionData);

        return $this;
    }

    protected function getSessionValue($key = null)
    {
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

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
        $this->getHelper('Data\Session')->setValue($this->sessionKey, null);
    }

    //########################################

    private function setWizardStep($step)
    {
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK, $step);
    }

    //########################################

    private function isCreationModeListingOnly()
    {
        return $this->getSessionValue('creation_mode') ===
        \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
    }

    //########################################
}
