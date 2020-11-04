<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Transferring;

/**
 * @method \Ess\M2ePro\Model\Listing getListing()
 */
class Destination extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory */
    private $amazonFactory;

    /** @var \Magento\Store\Model\StoreManager $storeManager */
    private $storeManager;

    protected $_template = 'amazon/listing/transferring/destination.phtml';

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->amazonFactory = $amazonFactory;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        $this->setId('amazonListingTransferringDestination');

        parent::_construct();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Account\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAccounts()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();
        $accountCollection->setOrder('title', 'ASC');
        $accountCollection->addFieldToFilter('id', [
            'neq' => (int)$this->getListing()->getAccount()->getId()
        ]);

        return $accountCollection;
    }

    //----------------------------------------

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore($this->getListing()->getStoreId());
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                <<<HTML
The Sell on Another Marketplace feature allows you to list products on multiple Amazon marketplaces.
To do it, you have to choose From and To Accounts, Marketplaces, Store Views and Listings.
Click <a href="%url%" target="_blank">here</a> to learn more detailed information.
HTML
                ,
                $this->getHelper('Module_Support')->getDocumentationArticleUrl('x/i4KCAQ')
            ),
            'style' => 'margin-top: 15px;',
            'title' => $this->__('Sell on Another Marketplace')
        ]);

        $parentHtml = parent::_toHtml();

        return <<<HTML
{$helpBlock->toHtml()}
<div class="grid">{$parentHtml}</div>
HTML;
    }

    protected function _beforeToHtml()
    {
        $storeSwitcherBlock = $this->createBlock('StoreSwitcher')->setData('id', 'to_store_id');
        $this->setChild('store_switcher', $storeSwitcherBlock);

        return parent::_beforeToHtml();
    }

    //########################################
}
