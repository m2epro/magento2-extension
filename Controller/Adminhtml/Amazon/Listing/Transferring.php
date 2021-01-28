<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class Transferring extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    //########################################

    /** @var \Ess\M2ePro\Model\Listing $listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring */
    protected $transferring;

    /** @var \Ess\M2ePro\Model\Amazon\Template\Manager $templateManager */
    protected $templateManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring,
        \Ess\M2ePro\Model\Amazon\Template\Manager $templateManager
    ) {
        parent::__construct($amazonFactory, $context);

        $this->transferring = $transferring;
        $this->templateManager = $templateManager;
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings');
    }

    //########################################

    public function execute()
    {
        $this->listing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $this->transferring->setListing($this->listing);

        $productsIds = $this->getRequest()->getParam('products_ids');
        if (!empty($productsIds)) {
            $this->transferring->clearSession();
            $this->transferring->setProductsIds(explode(',', $productsIds));
        }

        switch ((int)$this->getRequest()->getParam('step')) {
            case 1:
                return $this->destinationStep();

            case 2:
                return $this->listingStep();

            case 3:
                return $this->productsStep();

            default:
                return $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
        }
    }

    //########################################

    protected function destinationStep()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Transferring\Destination $block */
        $block = $this->createBlock(
            'Amazon_Listing_Transferring_Destination',
            '',
            [
                'data' => [
                    'listing' => $this->listing
                ]
            ]
        );

        if (!$block->getAccounts()->count()) {
            $this->setJsonContent(
                [
                    'error'   => true,
                    'message' => $this->getHelper('Module_Translation')->__(
                        <<<HTML
To use the Sell on Another Marketplace feature properly,
you need to add one more account to M2E Pro under <i>Amazon Integration > Configuration > Accounts</i>.
<br/>
<br/>
Click <a href="%url%" target="_blank">here</a> to learn about the Sell on Another Marketplace feature.
HTML
                        ,
                        $this->getHelper('Module_Support')->getDocumentationArticleUrl('x/iICzAQ')
                    )
                ]
            );

            return $this->getResult();
        }

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    protected function listingStep()
    {
        $this->transferring->setTargetListingId($this->getRequest()->getParam('to_listing_id'));

        if (!$this->transferring->isTargetListingNew()) {
            return $this->_redirect(
                '*/amazon_listing/transferring/index',
                [
                    '_current'   => true,
                    'listing_id' => $this->listing->getId(),
                    'step'       => 3
                ]
            );
        }

        $templates = $this->listing->getMarketplaceId() == $this->getRequest()->getParam('marketplace_id')
            ? $this->templateManager->getAllTemplates()
            : $this->templateManager->getNotMarketplaceDependentTemplates();

        $sessionData = [
            'account_id'     => (int)$this->getRequest()->getParam('account_id'),
            'marketplace_id' => (int)$this->getRequest()->getParam('marketplace_id'),
            'store_id'       => (int)$this->getRequest()->getParam('store_id')
        ];

        foreach ($templates as $nick) {
            $this->templateManager->setTemplate($nick);
            $sessionData["template_{$nick}_id"] = $this->listing->getChildObject()->getData(
                $this->templateManager->getTemplateIdColumnName()
            );
        }

        $amazonListing = $this->listing->getChildObject();

        // Selling Settings
        $sessionData['sku_mode'] = $amazonListing->getSkuMode();
        $sessionData['sku_custom_attribute'] = $amazonListing->getData('sku_custom_attribute');

        $sessionData['sku_modification_mode'] = $amazonListing->getSkuModificationMode();
        $sessionData['sku_modification_custom_value'] = $amazonListing->getData('sku_modification_custom_value');

        $sessionData['generate_sku_mode'] = $amazonListing->getGenerateSkuMode();

        $sessionData['condition_mode'] = $amazonListing->getConditionMode();
        $sessionData['condition_value'] = $amazonListing->getData('condition_value');
        $sessionData['condition_custom_attribute'] = $amazonListing->getData('condition_custom_attribute');

        $sessionData['condition_note_mode'] = $amazonListing->getConditionNoteMode();
        $sessionData['condition_note_value'] = $amazonListing->getData('condition_note_value');

        $sessionData['gift_wrap_mode'] = $amazonListing->getGiftWrapMode();
        $sessionData['gift_wrap_attribute'] = $amazonListing->getData('gift_wrap_attribute');

        $sessionData['gift_message_mode'] = $amazonListing->getGiftMessageMode();
        $sessionData['gift_message_attribute'] = $amazonListing->getData('gift_message_attribute');

        $sessionData['handling_time_mode'] = $amazonListing->getHandlingTimeMode();
        $sessionData['handling_time_value'] = $amazonListing->getData('handling_time_value');
        $sessionData['handling_time_custom_attribute'] = $amazonListing->getData('handling_time_custom_attribute');

        $sessionData['restock_date_source'] = $amazonListing->getRestockDateMode();
        $sessionData['restock_date_value'] = $amazonListing->getData('restock_date_value');
        $sessionData['restock_date_custom_attribute'] = $amazonListing->getData('restock_date_custom_attribute');

        // Search Settings
        $sessionData['general_id_mode'] = $amazonListing->getGeneralIdMode();
        $sessionData['general_id_custom_attribute'] = $amazonListing->getData('general_id_custom_attribute');

        $sessionData['worldwide_id_mode'] = $amazonListing->getWorldwideIdMode();
        $sessionData['worldwide_id_custom_attribute'] = $amazonListing->getData('worldwide_id_custom_attribute');

        $sessionData['search_by_magento_title_mode'] = $amazonListing->getSearchByMagentoTitleMode();

        $this->getHelper('Data_Session')->setValue(
            \Ess\M2ePro\Model\Amazon\Listing::CREATE_LISTING_SESSION_DATA,
            $sessionData
        );

        return $this->_redirect(
            '*/amazon_listing_create/index',
            [
                '_current'      => true,
                'step'          => 1,
                'creation_mode' => \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY,
                'wizard'        => true
            ]
        );
    }

    protected function productsStep()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Sell on Another Marketplace')
        );

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Transferring\Products $block */
        $block = $this->createBlock(
            'Amazon_Listing_Transferring_Products',
            '',
            [
                'data' => [
                    'listing' => $this->listing
                ]
            ]
        );

        $this->addContent($block);

        return $this->getResultPage();
    }

    //########################################
}
