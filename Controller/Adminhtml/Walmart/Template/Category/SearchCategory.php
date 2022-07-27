<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

class SearchCategory extends Category
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
        if (!$keywords = $this->getRequest()->getParam('query', '')) {
            $this->setJsonContent([]);
            return $this->getResult();
        }

        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                $this->dbStructureHelper->getTableNameWithPrefix('m2epro_walmart_dictionary_category')
            )
            ->where('is_leaf = 1')
            ->where('marketplace_id = ?', $this->getRequest()->getParam('marketplace_id'));

        $where = [];
        $where[] = "browsenode_id = {$connection->quote($keywords)}";

        foreach (explode(' ', $keywords) as $part) {
            $part = trim($part);
            if ($part == '') {
                continue;
            }

            $part = $connection->quote('%'.$part.'%');
            $where[] = "keywords LIKE {$part} OR title LIKE {$part}";
        }

        $select->where(implode(' OR ', $where))
            ->limit(200)
            ->order('id ASC');

        $categories = [];
        $queryStmt = $select->query();

        while ($row = $queryStmt->fetch()) {
            $this->formatCategoryRow($row);
            $categories[] = $row;
        }

        $this->setJsonContent($categories);
        return $this->getResult();
    }
}
