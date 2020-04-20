<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay\Category;

/**
 * Class \Ess\M2ePro\Helper\Component\Ebay\Category\Store
 */
class Store extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $modelFactory;
    protected $activeRecordFactory;
    protected $ebayParentFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayParentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayParentFactory = $ebayParentFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getPath($categoryId, $accountId, $delimiter = ' > ')
    {
        $account = $this->ebayParentFactory->getCachedObjectLoaded('Account', $accountId);
        $categories = $account->getChildObject()->getEbayStoreCategories();

        $pathData = [];

        while (true) {
            $currentCategory = null;

            foreach ($categories as $category) {
                if ($category['category_id'] == $categoryId) {
                    $currentCategory = $category;
                    break;
                }
            }

            if ($currentCategory === null) {
                break;
            }

            $pathData[] = $currentCategory['title'];

            if ($currentCategory['parent_id'] == 0) {
                break;
            }

            $categoryId = $currentCategory['parent_id'];
        }

        array_reverse($pathData);
        return implode($delimiter, $pathData);
    }

    //########################################

    public function getSameTemplatesData($ids)
    {
        return $this->getHelper('Component_Ebay_Category')->getSameTemplatesData(
            $ids,
            $this->activeRecordFactory->getObject('Ebay_Template_OtherCategory')->getResource()->getMainTable(),
            ['category_secondary','store_category_main','store_category_secondary']
        );
    }

    public function isExistDeletedCategories()
    {
        /** @var $connection \Magento\Framework\DB\Adapter\AdapterInterface */
        $connection = $this->resourceConnection->getConnection();

        $etocTable = $this->activeRecordFactory->getObject('Ebay_Template_OtherCategory')
            ->getResource()->getMainTable();
        $eascTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_account_store_category');

        $primarySelect = $connection->select();
        $primarySelect->from(
            ['primary_table' => $etocTable]
        )
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns([
                'store_category_main_id as category_id',
                'account_id',
            ])
            ->where('store_category_main_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
            ->group(['category_id', 'account_id']);

        $secondarySelect = $connection->select();
        $secondarySelect->from(
            ['secondary_table' => $etocTable]
        )
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns([
                'store_category_secondary_id as category_id',
                'account_id',
            ])
            ->where('store_category_secondary_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
            ->group(['category_id', 'account_id']);

        $unionSelect = $connection->select();
        $unionSelect->union([
            $primarySelect,
            $secondarySelect,
        ]);

        $mainSelect = $connection->select();
        $mainSelect->reset()
            ->from(['main_table' => $unionSelect])
            ->joinLeft(
                ['easc' => $eascTable],
                'easc.account_id = main_table.account_id
                    AND easc.category_id = main_table.category_id'
            )
            ->where('easc.category_id IS NULL');

        return $connection->query($mainSelect)->fetchColumn() !== false;
    }

    //########################################
}
