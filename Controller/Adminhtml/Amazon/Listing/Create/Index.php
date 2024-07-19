<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Create;

use Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling\Form as CreateSellingForm;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $helperWizard;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring */
    private $transferring;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $helperDataSession;
    private \Ess\M2ePro\Model\Amazon\Listing\OfferImagesFormService $offerImagesService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\OfferImagesFormService $offerImagesService,
        \Ess\M2ePro\Helper\Module\Wizard $helperWizard,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring,
        \Ess\M2ePro\Helper\Data\Session $helperDataSession,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->helperWizard = $helperWizard;
        $this->transferring = $transferring;
        $this->helperDataSession = $helperDataSession;
        $this->offerImagesService = $offerImagesService;

        parent::__construct($amazonFactory, $context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

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

        $this->setPageHelpLink('m2e-pro-listings');
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
            $this->setSessionValue('marketplace_id', (int)$post['marketplace_id']);
            $this->setSessionValue('account_id', (int)$post['account_id']);
            $this->setSessionValue('store_id', (int)$post['store_id']);

            $this->_redirect('*/*/index', ['_current' => true, 'step' => 2]);

            return;
        }

        $listingOnlyMode = \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
        if ($this->getRequest()->getParam('creation_mode') == $listingOnlyMode) {
            $this->setSessionValue('creation_mode', $listingOnlyMode);
        }

        $this->setWizardStep('listingGeneral');

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\General::class)
        );
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

            /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling\Form $createSellingForm */
            $createSellingForm = $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling\Form::class
            );
            $dataKeys = array_keys($createSellingForm->getDefaultFieldsValues());

            $post = $this->getRequest()->getPost();
            foreach ($dataKeys as $key) {
                $this->setSessionValue($key, $post[$key]);
            }

            $listing = $this->createListing();

            if ($listingId = $this->getRequest()->getParam('listing_id')) {
                $this->transferring->setListing(
                    $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId)
                );

                $this->clearSession();
                $this->transferring->setTargetListingId($listing->getId());

                return $this->_redirect(
                    '*/amazon_listing/transferring/index',
                    [
                        'listing_id' => $listingId,
                        'step' => 3,
                    ]
                );
            }

            if ($this->isCreationModeListingOnly()) {
                // closing window for Unmanaged products moving in new listing creation

                return $this->getRawResult()->setContents("<script>window.close();</script>");
            }

            $this->clearSession();

            return $this->_redirect(
                '*/amazon_listing_product_add/index',
                [
                    'id' => $listing->getId(),
                    'new_listing' => 1,
                    'wizard' => $this->getRequest()->getParam('wizard'),
                ]
            );
        }

        $this->setWizardStep('listingSelling');

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling::class)
        );
    }

    // ----------------------------------------

    protected function createListing()
    {
        $data = $this->getSessionValue();

        if ($this->getSessionValue('restock_date_value') === '') {
            $data['restock_date_value'] = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        } else {
            $timestamp = \Ess\M2ePro\Helper\Date::parseDateFromLocalFormat(
                $this->getSessionValue('restock_date_value')
            );
            $data['restock_date_value'] = gmdate('Y-m-d H:i:s', $timestamp);
        }

        // Product identifiers
        // ---------------------------------------
        $data[\Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE] =
            !empty($data[CreateSellingForm::FIELD_NAME_GENERAL_ID_ATTRIBUTE])
                ? $data[CreateSellingForm::FIELD_NAME_GENERAL_ID_ATTRIBUTE]
                : null;

        $data[\Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_WORLDWIDE_ID_ATTRIBUTE] =
            !empty($data[CreateSellingForm::FIELD_NAME_WORLDWIDE_ID_ATTRIBUTE])
                ? $data[CreateSellingForm::FIELD_NAME_WORLDWIDE_ID_ATTRIBUTE]
                : null;

        if (isset($data['offer_images']) && is_array($data['offer_images'])) {
            $offerImages = [];
            if ($data['condition_value'] != \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NEW) {
                $offerImages = $this->offerImagesService->prepareOfferImagesData($data['offer_images']);
            }

            $data['offer_images'] = $this->offerImagesService->convertToString($offerImages);
        }

        // ---------------------------------------

        // Add new Listing
        // ---------------------------------------
        $listing = $this->amazonFactory->getObject('Listing')->addData($data)->save();
        // ---------------------------------------

        // Set message to log
        // ---------------------------------------
        $tempLog = $this->activeRecordFactory->getObject('Listing_Log');
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $tempLog->addListingMessage(
            $listing->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            $tempLog->getResource()->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_LISTING,
            'Listing was Added',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );

        // ---------------------------------------

        return $listing;
    }

    // ----------------------------------------

    protected function getMarketplaceId()
    {
        $accountObj = $this->amazonFactory->getCachedObjectLoaded('Account', (int)$this->getSessionValue('account_id'));

        return (int)$accountObj->getChildObject()->getMarketplaceId();
    }

    // ----------------------------------------

    protected function setSessionValue($key, $value)
    {
        $sessionData = $this->getSessionValue();
        $sessionData[$key] = $value;

        $this->helperDataSession->setValue(
            \Ess\M2ePro\Model\Amazon\Listing::CREATE_LISTING_SESSION_DATA,
            $sessionData
        );

        return $this;
    }

    protected function getSessionValue($key = null)
    {
        $sessionData = $this->helperDataSession->getValue(
            \Ess\M2ePro\Model\Amazon\Listing::CREATE_LISTING_SESSION_DATA
        );

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
        $this->helperDataSession->setValue(
            \Ess\M2ePro\Model\Amazon\Listing::CREATE_LISTING_SESSION_DATA,
            null
        );
    }

    // ----------------------------------------

    private function isCreationModeListingOnly()
    {
        return $this->getSessionValue('creation_mode') == \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
    }

    // ----------------------------------------

    private function setWizardStep($step)
    {
        if (!$this->helperWizard->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $this->helperWizard->setStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK, $step);
    }
}
