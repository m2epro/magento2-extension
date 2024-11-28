<?php

namespace Ess\M2ePro\Model\Ebay\Account\Store\Category;

class Update
{
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDatabaseStructure;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;
    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructure,
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->moduleDatabaseStructure = $moduleDatabaseStructure;
        $this->componentEbayCategory = $componentEbayCategory;
        $this->modelFactory = $modelFactory;
    }

    public function process(\Ess\M2ePro\Model\Ebay\Account $account): void
    {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcherObj */
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'account',
            'get',
            'store',
            [],
            null,
            null,
            $account->getId()
        );

        $dispatcherObj->process($connectorObj);
        $data = $connectorObj->getResponseData();

        if (!is_array($data)) {
            return;
        }

        if (!empty($data['data'])) {
            $this->updateAccount($data['data'], $account);
        }

        $connection = $account->getResource()->getConnection();

        $tableAccountStoreCategories = $this->moduleDatabaseStructure
            ->getTableNameWithPrefix('m2epro_ebay_account_store_category');

        $connection->delete($tableAccountStoreCategories, ['account_id = ?' => $account->getId()]);

        $this->componentEbayCategory->removeStoreRecent();

        if (empty($data['categories'])) {
            return;
        }

        foreach ($data['categories'] as $item) {
            $row = [
                'account_id' => $account->getId(),
                'category_id' => $item['category_id'],
                'parent_id' => $item['parent_id'],
                'title' => $item['title'],
                'sorder' => $item['sorder'],
                'is_leaf' => $item['is_leaf'],
            ];

            $connection->insertOnDuplicate($tableAccountStoreCategories, $row);
        }
    }

    private function updateAccount(array $responseData, \Ess\M2ePro\Model\Ebay\Account $account): void
    {
        if (!empty($responseData['title'])) {
            $account->setEbayStoreTitle($responseData['title']);
        }

        if (!empty($responseData['url'])) {
            $account->setEbayStoreUrl($responseData['url']);
        }

        if (!empty($responseData['subscription_level'])) {
            $account->setEbayStoreSubscriptionLevel($responseData['subscription_level']);
        }

        if (!empty($responseData['description'])) {
            $account->setEbayStoreDescription($responseData['description']);
        }

        $account->save();
    }
}
