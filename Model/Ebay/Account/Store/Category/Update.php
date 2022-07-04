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

    /**
     * @param \Ess\M2ePro\Model\Ebay\Account $account
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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

        $infoKeys = [
            'title',
            'url',
            'subscription_level',
            'description',
        ];

        $dataForUpdate = [];
        foreach ($infoKeys as $key) {
            if (!isset($data['data'][$key])) {
                $dataForUpdate['ebay_store_' . $key] = '';
                continue;
            }
            $dataForUpdate['ebay_store_' . $key] = $data['data'][$key];
        }
        $account->addData($dataForUpdate);
        $account->save();

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
                'account_id'  => $account->getId(),
                'category_id' => $item['category_id'],
                'parent_id'   => $item['parent_id'],
                'title'       => $item['title'],
                'sorder'      => $item['sorder'],
                'is_leaf'     => $item['is_leaf'],
            ];

            $connection->insertOnDuplicate($tableAccountStoreCategories, $row);
        }
    }
}
