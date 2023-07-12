<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

class ExportCsvAllItemsGrid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    /** @var \Ess\M2ePro\Helper\Data\FileExport */
    private $fileExportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\FileExport $fileExportHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->fileExportHelper = $fileExportHelper;
    }

    public function execute()
    {
        $gridName = \Ess\M2ePro\Helper\Data\FileExport::ALL_ITEMS_GRID;

        $content = $this->_view->getLayout()
                               ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AllItems\Grid::class)
                               ->getCsv();

        return $this->fileExportHelper->createFile($gridName, $content);
    }
}
