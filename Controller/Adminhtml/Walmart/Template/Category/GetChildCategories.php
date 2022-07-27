<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

class GetChildCategories extends Category
{
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructureHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dbStructureHelper = $dbStructureHelper;
    }

    public function execute()
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->dbStructureHelper->getTableNameWithPrefix('m2epro_walmart_dictionary_category')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getPost('marketplace_id'))
            ->order('title ASC');

        $parentCategoryId = $this->getRequest()->getPost('parent_category_id');
        empty($parentCategoryId) ? $select->where('parent_category_id IS NULL')
            : $select->where('parent_category_id = ?', $parentCategoryId);

        $queryStmt = $select->query();
        $tempCategories = [];

        $sortIndex = 0;
        while ($row = $queryStmt->fetch()) {
            $this->formatCategoryRow($row);
            $this->isItOtherCategory($row) ? $tempCategories[10000] = $row
                : $tempCategories[$sortIndex++] = $row;
        }

        ksort($tempCategories);
        $this->setJsonContent(array_values($tempCategories));
        return $this->getResult();
    }

    // ---------------------------------------

    private function isItOtherCategory($row)
    {
        if ($row['path'] === null) {
            return false;
        }

        $parentTitle = explode('>', $row['path']);
        $parentTitle = array_pop($parentTitle);

        return preg_match("/^.* \({$parentTitle}\)$/i", $row['title']);
    }

    //########################################
}
