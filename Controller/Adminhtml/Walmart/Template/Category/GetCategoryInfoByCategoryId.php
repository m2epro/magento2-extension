<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

class GetCategoryInfoByCategoryId extends Category
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
        $category = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->dbStructureHelper->getTableNameWithPrefix('m2epro_walmart_dictionary_category')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getPost('marketplace_id'))
            ->where('category_id = ?', $this->getRequest()->getPost('category_id'))
            ->query()
            ->fetch();

        if (!$category) {
            $this->setAjaxContent(null, false);
            return $this->getResult();
        }

        $this->formatCategoryRow($category);
        $this->setJsonContent($category);
        return $this->getResult();
    }
}
