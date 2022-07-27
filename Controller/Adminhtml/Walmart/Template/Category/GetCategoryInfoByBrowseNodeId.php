<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

class GetCategoryInfoByBrowseNodeId extends Category
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
        $queryStmt = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->dbStructureHelper->getTableNameWithPrefix('m2epro_walmart_dictionary_category')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getPost('marketplace_id'))
            ->where('browsenode_id = ?', $this->getRequest()->getPost('browsenode_id'))
            ->query();

        $tempCategories = [];

        while ($row = $queryStmt->fetch()) {
            $this->formatCategoryRow($row);
            $tempCategories[] = $row;
        }

        if (empty($tempCategories)) {
            $this->setAjaxContent(null);
            return $this->getResult();
        }

        $dbCategoryPath = str_replace(' > ', '>', $this->getRequest()->getPost('category_path'));

        foreach ($tempCategories as $category) {
            $tempCategoryPath = $category['path'] !== null ? $category['path'] .'>'. $category['title']
                : $category['title'];
            if ($tempCategoryPath == $dbCategoryPath) {
                $this->setJsonContent($category);
                return $this->getResult();
            }
        }

        $this->setJsonContent($tempCategories[0]);
        return $this->getResult();
    }
}
