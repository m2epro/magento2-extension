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
        $step = (int)$this->getRequest()->getParam('step');

        switch ($step) {
            case 1:
                $this->stepOne();
                break;
            case 2:
                $this->stepTwo();
                if ($this->getRequest()->isPost() && $this->isCreationModeListingOnly()) {
                    // closing window for Unmanaged products moving in new listing creation

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

            return;
        }

        $this->setWizardStep('listingGeneral');

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            // clear session data if user came back to the first step and changed the marketplace
            // ---------------------------------------
            if ($this->getSessionValue('marketplace_id')
                && (int)$this->getSessionValue('marketplace_id') != (int)$post['marketplace_id']
            ) {
                $this->clearSession();
            }

            $this->setSessionValue('title', strip_tags($post['title']));
            $this->setSessionValue('account_id', (int)$post['account_id']);
            $this->setSessionValue('marketplace_id', (int)$post['marketplace_id']);
            $this->setSessionValue('store_id', (int)$post['store_id']);

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 2]);

            return;
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
            $this->getSessionValue('marketplace_id') === null
        ) {
            $this->clearSession();
            $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);

            return;
        }

        if ($this->getRequest()->isPost()) {
            $dataKeys = $this->createBlock(
                'Ebay_Listing_Create_Templates_Form'
            )->getDefaultFieldsValues();

            $post = $this->getRequest()->getPost();
            foreach ($dataKeys as $key => $value) {
                $this->setSessionValue($key, $post[$key]);
            }

            $listing = $this->createListing();

            //todo Transferring move in another place?
            if ($listingId = $this->getRequest()->getParam('listing_id')) {
                $this->transferring->setListing(
                    $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId)
                );

                $this->clearSession();
                $this->transferring->setTargetListingId($listing->getId());

                $this->_redirect(
                    '*/ebay_listing/transferring/index',
                    [
                        'listing_id' => $listingId,
                        'step'       => 3,
                    ]
                );

                return;
            }

            if ($this->isCreationModeListingOnly()) {
                // closing window for Unmanaged products moving in new listing creation
                $this->getRawResult()->setContents("<script>window.close();</script>");
                return;
            }

            $this->clearSession();

            if ((bool)$this->getRequest()->getParam('wizard', false)) {
                $this->setWizardStep('sourceMode');

                $this->_redirect('*/wizard_installationEbay');
                return;
            }

            $this->_redirect(
                '*/ebay_listing_product_add/sourceMode',
                [
                    'id'               => $listing->getId(),
                    'listing_creation' => true
                ]
            );

            return;
        }

        $this->setWizardStep('listingTemplates');
        $this->addContent($this->createBlock('Ebay_Listing_Create_Templates'));
    }

    //########################################

    private function createListing()
    {
        $data = $this->getSessionValue();

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $data['marketplace_id']);

        $data['parts_compatibility_mode'] = null;
        if ($marketplace->getChildObject()->isMultiMotorsEnabled()) {
            $data['parts_compatibility_mode'] = \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_KTYPES;
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
