<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing;

use Ess\M2ePro\Controller\Adminhtml\Context;

class GetUpdatedPriceBySkus extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    protected $localeCurrency;

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    )
    {
        $this->localeCurrency = $localeCurrency;
        parent::__construct($amazonFactory, $context);
    }

    public function execute()
    {
        $groupedSkus = $this->getRequest()->getParam('grouped_skus');

        if (empty($groupedSkus)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        $groupedSkus = $this->getHelper('Data')->jsonDecode($groupedSkus);
        $resultPrices = array();

        foreach ($groupedSkus as $accountId => $skus) {
            /** @var $account \Ess\M2ePro\Model\Account */
            $account = $this->amazonFactory->getCachedObjectLoaded('Account', $accountId);

            /** @var \Ess\M2ePro\Model\Amazon\Account $amazonAccount */
            $amazonAccount = $account->getChildObject();

            $currency = $amazonAccount->getMarketplace()->getChildObject()->getDefaultCurrency();

            /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General */
            $repricingSynchronization = $this->modelFactory->getObject('Amazon\Repricing\Synchronization\General');
            $repricingSynchronization->setAccount($account);
            $repricingSynchronization->run($skus);

            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
            $listingProductCollection->getSelect()->joinLeft(
                array('l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
                'l.id = main_table.listing_id',
                array()
            );
            $listingProductCollection->addFieldToFilter('l.account_id', $accountId);
            $listingProductCollection->addFieldToFilter('sku', array('in' => $skus));

            $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingProductCollection->getSelect()->columns(
                array(
                    'second_table.sku',
                    'second_table.online_regular_price'
                )
            );

            $listingsProductsData = $listingProductCollection->getData();

            foreach ($listingsProductsData as $listingProductData) {
                $price = $this->localeCurrency
                    ->getCurrency($currency)
                    ->toCurrency($listingProductData['online_regular_price']);
                $resultPrices[$accountId][$listingProductData['sku']] = $price;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $listingOtherCollection */
            $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();

            $listingOtherCollection->addFieldToFilter('account_id', $accountId);
            $listingOtherCollection->addFieldToFilter('sku', array('in' => $skus));

            $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingOtherCollection->getSelect()->columns(
                array(
                    'second_table.sku',
                    'second_table.online_price'
                )
            );

            $listingsOthersData = $listingOtherCollection->getData();

            foreach ($listingsOthersData as $listingOtherData) {
                $price = $this->localeCurrency->getCurrency($currency)->toCurrency($listingOtherData['online_price']);
                $resultPrices[$accountId][$listingOtherData['sku']] = $price;
            }
        }

        $this->setJsonContent($resultPrices);

        return $this->getResult();
    }
}