<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;
use Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Edit;

class GetCategoryChooserHtml extends Description
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Category */
    protected $categoryHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Helper\Component\Amazon\Category $categoryHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperDataGlobalData = $helperDataGlobalData;
        $this->categoryHelper = $categoryHelper;
    }

    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Edit $editBlock */
        $editBlock = $this->getLayout()->createBlock(Edit::class);

        $editBlock->setMarketplaceId($this->getRequest()->getPost('marketplace_id'));

        $browseNodeId = $this->getRequest()->getPost('browsenode_id');
        $categoryPath = $this->getRequest()->getPost('category_path');

        $recentlySelectedCategories = $this->categoryHelper->getRecent(
            $this->getRequest()->getPost('marketplace_id'),
            ['browsenode_id' => $browseNodeId, 'path' => $categoryPath]
        );

        if (empty($recentlySelectedCategories)) {
            $this->helperDataGlobalData->setValue('category_chooser_hide_recent', true);
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

    //########################################
}
