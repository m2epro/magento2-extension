<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class SearchCategory extends Description
{
    //########################################

    public function execute()
    {
        if (!$keywords = $this->getRequest()->getParam('query', '')) {
            $this->setJsonContent([]);
            return $this->getResult();
        }

        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('m2epro_amazon_dictionary_category'))
            ->where('is_leaf = 1')
            ->where('marketplace_id = ?', $this->getRequest()->getParam('marketplace_id'));

        $where = array();
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

        $categories = array();
        $queryStmt = $select->query();

        while ($row = $queryStmt->fetch()) {
            $this->formatCategoryRow($row);
            $categories[] = $row;
        }

        $this->setJsonContent($categories);
        return $this->getResult();
    }

    //########################################
}