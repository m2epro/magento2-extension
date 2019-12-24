<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Create;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Create\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    protected $sessionKey = 'amazon_listing_create';

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        // Check clear param
        // ---------------------------------------
        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return;
        }
        // ---------------------------------------

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

        $this->setPageHelpLink('x/AgItAQ');
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('New Listing Creation'));

        return $this->getResult();
    }

    protected function stepOne()
    {
        if ($this->getRequest()->isPost()) {
            // save data
            $post = $this->getRequest()->getPost();
            // ---------------------------------------

            $this->setSessionValue('title', strip_tags($post['title']));
            $this->setSessionValue('account_id', (int)$post['account_id']);
            $this->setSessionValue('store_id', (int)$post['store_id']);

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 2]);
            return;
        }

        $this->setWizardStep('listingGeneral');

        $this->addContent($this->createBlock('Amazon_Listing_Create_General'));
    }

    // ---------------------------------------

    protected function stepTwo()
    {
        if ($this->getSessionValue('account_id') === null) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return;
        }

        if ($this->getRequest()->isPost()) {
            $this->setSessionValue('marketplace_id', $this->getMarketplaceId());

            $dataKeys = array_keys(
                $this->createBlock('Amazon_Listing_Create_Selling_Form')->getDefaultFieldsValues()
            );

            $post = $this->getRequest()->getPost();
            foreach ($dataKeys as $key) {
                $this->setSessionValue($key, $post[$key]);
            }

            $this->_redirect('*/*/index', ['_current' => true, 'step'=>'3']);
            return;
        }

        $this->setWizardStep('listingSelling');

        $this->addContent($this->createBlock('Amazon_Listing_Create_Selling'));
    }

    // ---------------------------------------

    protected function stepThree()
    {
        if ($this->getSessionValue('account_id') === null) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
            return;
        }

        if ($this->getRequest()->isPost()) {
            $dataKeys = array_keys(
                $this->createBlock('Amazon_Listing_Create_Search_Form')->getDefaultFieldsValues()
            );

            $post = $this->getRequest()->getPost();
            foreach ($dataKeys as $key) {
                $this->setSessionValue($key, $post[$key]);
            }

            $listing = $this->createListing();
            $this->clearSession();

            if ($this->isCreationModeListingOnly()) {
                // closing window for 3rd party products moving in new listing creation

                return $this->getRawResult()->setContents("<script>window.close();</script>");
            }

            $this->_redirect(
                '*/amazon_listing_product_add/index',
                [
                    'id' => $listing->getId(),
                    'new_listing' => 1,
                    'wizard' => $this->getRequest()->getParam('wizard')
                ]
            );
            return;
        }

        $this->setWizardStep('listingSearch');

        $this->addContent($this->createBlock('Amazon_Listing_Create_Search'));
    }

    //########################################

    protected function createListing()
    {
        $sessionData = $this->getSessionValue();

        if ($sessionData['restock_date_value'] === '') {
            $sessionData['restock_date_value'] = $this->getHelper('Data')->getCurrentGmtDate();
        } else {
            $timestamp = $this->getHelper('Data')->parseTimestampFromLocalizedFormat(
                $sessionData['restock_date_value'],
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT
            );
            $sessionData['restock_date_value'] = $this->getHelper('Data')->getDate($timestamp);
        }

        // Add new Listing
        // ---------------------------------------
        $listing = $this->amazonFactory->getObject('Listing')->addData($sessionData)->save();
        // ---------------------------------------

        // Set message to log
        // ---------------------------------------
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($listing->getComponentMode());
        $tempLog->addListingMessage(
            $listing->getId(),
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

        return $listing;
    }

    //########################################

    protected function getMarketplaceId()
    {
        $accountObj = $this->amazonFactory->getCachedObjectLoaded('Account', (int)$this->getSessionValue('account_id'));
        return (int)$accountObj->getChildObject()->getMarketplaceId();
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

    // ---------------------------------------

    private function clearSession()
    {
        $this->getHelper('Data\Session')->setValue($this->sessionKey, null);
    }

    //########################################

    private function isCreationModeListingOnly()
    {
        return $this->getRequest()->getParam('creation_mode') ==
            \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
    }

    //########################################

    private function setWizardStep($step)
    {
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK, $step);
    }

    //########################################
}
