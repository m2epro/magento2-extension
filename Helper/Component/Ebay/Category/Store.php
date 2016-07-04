<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay\Category;

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
    )
    {
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

        $pathData = array();

        while (true) {

            $currentCategory = NULL;

            foreach ($categories as $category) {
                if ($category['category_id'] == $categoryId) {
                    $currentCategory = $category;
                    break;
                }
            }

            if (is_null($currentCategory)) {
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
        return $this->getHelper('Component\Ebay\Category')->getSameTemplatesData(
            $ids, $this->activeRecordFactory->getObject('Ebay\Template\OtherCategory')->getResource()->getMainTable(),
            array('category_secondary','store_category_main','store_category_secondary')
        );
    }

    public function isExistDeletedCategories()
    {
        /** @var $connection \Magento\Framework\DB\Adapter\AdapterInterface */
        $connection = $this->resourceConnection->getConnection();

        $etocTable = $this->activeRecordFactory->getObject('Ebay\Template\OtherCategory')
            ->getResource()->getMainTable();
        $eascTable = $this->resourceConnection->getTableName('m2epro_ebay_account_store_category');

        $primarySelect = $connection->select();
        $primarySelect->from(
                array('primary_table' => $etocTable)
            )
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(array(
                'store_category_main_id as category_id',
                'account_id',
            ))
            ->where('store_category_main_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
            ->group(array('category_id', 'account_id'));

        $secondarySelect = $connection->select();
        $secondarySelect->from(
                array('secondary_table' => $etocTable)
            )
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(array(
                'store_category_secondary_id as category_id',
                'account_id',
            ))
            ->where('store_category_secondary_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
            ->group(array('category_id', 'account_id'));

        $unionSelect = $connection->select();
        $unionSelect->union(array(
            $primarySelect,
            $secondarySelect,
        ));

        $mainSelect = $connection->select();
        $mainSelect->reset()
            ->from(array('main_table' => $unionSelect))
            ->joinLeft(
                array('easc' => $eascTable),
                'easc.account_id = main_table.account_id
                    AND easc.category_id = main_table.category_id'
            )
            ->where('easc.category_id IS NULL');

        return $connection->query($mainSelect)->fetchColumn() !== false;
    }

    //########################################
}