<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class Transferring extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    //########################################

    /** @var \Ess\M2ePro\Model\Listing $listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Transferring $transferring */
    protected $transferring;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
    protected $templateManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\Ebay\Listing\Transferring $transferring,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager
    ) {
        parent::__construct($ebayFactory, $context);

        $this->transferring = $transferring;
        $this->templateManager = $templateManager;
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings');
    }

    //########################################

    public function execute()
    {
        $this->listing = $this->ebayFactory->getCachedObjectLoaded(
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
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Transferring\Destination $block */
        $block = $this->createBlock('Ebay_Listing_Transferring_Destination', '', [
            'data' => [
                'listing' => $this->listing
            ]
        ]);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //----------------------------------------

    protected function listingStep()
    {
        $this->transferring->setTargetListingId($this->getRequest()->getParam('to_listing_id'));

        if (!$this->transferring->isTargetListingNew()) {
            return $this->_redirect(
                '*/ebay_listing/transferring/index',
                [
                    '_current' => true,
                    'listing_id' => $this->listing->getId(),
                    'step' => 3
                ]
            );
        }

        $templates = $this->listing->getMarketplaceId() == $this->getRequest()->getParam('marketplace_id')
            ? $this->templateManager->getAllTemplates()
            : $this->templateManager->getNotMarketplaceDependentTemplates();

        $sessionData = [
            'account_id' => (int)$this->getRequest()->getParam('account_id'),
            'marketplace_id' => (int)$this->getRequest()->getParam('marketplace_id'),
            'store_id' => (int)$this->getRequest()->getParam('store_id')
        ];

        foreach ($templates as $nick) {
            $this->templateManager->setTemplate($nick);

            $sessionData["template_{$nick}_id"] = $this->listing->getChildObject()->getData(
                $this->templateManager->getTemplateIdColumnName()
            );
        }

        $this->getHelper('Data_Session')->setValue(
            \Ess\M2ePro\Model\Ebay\Listing::CREATE_LISTING_SESSION_DATA,
            $sessionData
        );

        return $this->_redirect(
            '*/ebay_listing_create/index',
            [
                '_current' => true,
                'step' => 1,
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

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Transferring\Products $block */
        $block = $this->createBlock(
            'Ebay_Listing_Transferring_Products',
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
