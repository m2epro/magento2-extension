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

    public function getPath($categoryId, $accountId, $delimiter = '>')
    {
        /** @var \Ess\M2ePro\Model\Account $account */
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

    public function isExistDeletedCategories()
    {
        $stmt = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['etsc' => $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory')->getResource()
                                                                                               ->getMainTable()
                ]
            )
            ->joinLeft(
                [
                    'edc' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_ebay_account_store_category')
                ],
                'edc.account_id = etsc.account_id AND edc.category_id = etsc.category_id'
            )
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(
                [
                    'category_id',
                    'account_id',
                ]
            )
            ->where('etsc.category_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
            ->where('edc.category_id IS NULL')
            ->group(
                ['etsc.category_id', 'etsc.account_id']
            )
            ->query();

        return $stmt->fetchColumn() !== false;
    }

    //########################################
}
