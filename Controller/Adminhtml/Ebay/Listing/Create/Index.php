<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Create;

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
                $this->_redirect('*/*/index', array('_current' => true, 'step' => 1));
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
            $this->getRequest()->setParam('clear',null);
            $this->_redirect('*/*/index',array('_current' => true, 'step' => 1));
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

            $this->_redirect('*/*/index', array('_current' => true, 'step' => 2));

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

        $this->addContent($this->createBlock('Ebay\Listing\Create\General'));
    }

    //########################################

    private function stepTwo()
    {
        // Check exist temp data
        // ---------------------------------------
        if (is_null($this->getSessionValue('account_id'))
            ||
            is_null($this->getSessionValue('marketplace_id'))
        ) {
            $this->clearSession();
            $this->_redirect('*/*/index', array('_current' => true, 'step' => 1));
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setWizardStep('listingGeneral');
        // ---------------------------------------

        $templateNicks = array(
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
        );

        // ---------------------------------------
        if ($this->getRequest()->isPost()) {
            // save data
            $post = $this->getRequest()->getPost();

            foreach ($templateNicks as $nick) {
                $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

                $this->setSessionValue("template_id_{$nick}", $templateData['id']);
                $this->setSessionValue("template_mode_{$nick}", $templateData['mode']);
            }

            $this->_redirect('*/*/index', array('_current' => true, 'step' => 3));
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->loadTemplatesDataFromSession();
        // ---------------------------------------

        // ---------------------------------------
        $data = array(
            'allowed_tabs' => array('general')
        );
        $content = $this->createBlock('Ebay\Listing\Edit');
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
        if (is_null($this->getSessionValue('account_id'))
            ||
            is_null($this->getSessionValue('marketplace_id'))
        ) {
            $this->clearSession();
            $this->_redirect('*/*/index', array('_current' => true, 'step' => 1));
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setWizardStep('listingSelling');
        // ---------------------------------------

        $templateNicks = array(
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
        );

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

            $this->_redirect('*/*/index', array('_current' => true, 'step' => 4));
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->loadTemplatesDataFromSession();
        // ---------------------------------------

        // ---------------------------------------
        $data = array(
            'allowed_tabs' => array('selling')
        );
        $content = $this->createBlock('Ebay\Listing\Edit');
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
        if (is_null($this->getSessionValue('account_id'))
            ||
            is_null($this->getSessionValue('marketplace_id'))
        ) {
            $this->clearSession();
            $this->_redirect('*/*/index', array('step' => 1,'_current' => true));
            return $this->getResult();
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setWizardStep('listingSynchronization');
        // ---------------------------------------

        $templateNicks = array(
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
        );

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

            if ((bool)$this->getRequest()->getParam('wizard',false)) {
                $this->setWizardStep('sourceMode');
                return $this->_redirect('*/wizard_installationEbay');
            }

            return $this->_redirect(
                '*/ebay_listing_product_add/sourceMode',
                array(
                    'id' => $listing->getId(),
                    'listing_creation' => true
                )
            );
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->loadTemplatesDataFromSession();
        // ---------------------------------------

        // ---------------------------------------
        $data = array(
            'allowed_tabs' => array('synchronization')
        );
        $content = $this->createBlock('Ebay\Listing\Edit');
        $content->setData($data);
        // ---------------------------------------

        $this->addContent($content);
        return $this->getResult();
    }

    //########################################

    private function createListing()
    {
        $data = array();
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

        $templateManager = $this->modelFactory->getObject('Ebay\Template\Manager');

        foreach ($templateManager->getAllTemplates() as $nick) {
            $manager = $this->modelFactory->getObject('Ebay\Template\Manager')->setTemplate($nick);

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

        return $model;
    }

    //########################################

    private function loadTemplatesDataFromSession()
    {
        // ---------------------------------------
        $listingTitle = $this->getSessionValue('listing_title');
        $this->getHelper('Data\GlobalData')->setValue('ebay_custom_template_title', $listingTitle);

        $dataLoader = $this->getHelper('Component\Ebay\Template\Switcher\DataLoader');
        $dataLoader->load($this->getHelper('Data\Session'), array('session_key' => $this->sessionKey));
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

    protected function getSessionValue($key = NULL)
    {
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

        if (is_null($sessionData)) {
            $sessionData = array();
        }

        if (is_null($key)) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : NULL;
    }

    //########################################

    private function clearSession()
    {
        $this->getHelper('Data\Session')->setValue($this->sessionKey, NULL);
    }

    //########################################

    private function setWizardStep($step)
    {
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK,$step);
    }

    //########################################

    private function isCreationModeListingOnly()
    {
        return $this->getSessionValue('creation_mode') ===
        \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
    }

    //########################################
}