<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;
use Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Edit;

class GetCategoryChooserHtml extends Category
{
    /** @var \Ess\M2ePro\Helper\Component\Walmart\Category */
    private $categoryHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Walmart\Category $categoryHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->categoryHelper = $categoryHelper;
        $this->globalData = $globalData;
    }

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Edit $editBlock */
        $editBlock = $this->getLayout()->createBlock(Edit::class);

        $editBlock->setMarketplaceId($this->getRequest()->getPost('marketplace_id'));

        $browseNodeId = $this->getRequest()->getPost('browsenode_id');
        $categoryPath = $this->getRequest()->getPost('category_path');

        $recentlySelectedCategories = $this->categoryHelper->getRecent(
            $this->getRequest()->getPost('marketplace_id'),
            ['browsenode_id' => $browseNodeId, 'path' => $categoryPath]
        );

        if (empty($recentlySelectedCategories)) {
            $this->globalData->setValue('category_chooser_hide_recent', true);
        }

        if ($browseNodeId && $categoryPath) {
            $editBlock->setSelectedCategory([
                'browseNodeId' => $browseNodeId,
                'categoryPath' => $categoryPath
            ]);
        }

        $this->setAjaxContent($editBlock->toHtml());
        return $this->getResult();
    }
}
