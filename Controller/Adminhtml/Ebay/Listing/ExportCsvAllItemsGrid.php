<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class ExportCsvAllItemsGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Helper\Data\FileExport */
    private $fileExportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\FileExport $fileExportHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->fileExportHelper = $fileExportHelper;
    }

    public function execute()
    {
        $gridName = \Ess\M2ePro\Helper\Data\FileExport::ALL_ITEMS_GRID;

        $content = $this->_view->getLayout()
                               ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AllItems\Grid::class)
                               ->getCsv();

        return $this->fileExportHelper->createFile($gridName, $content);
    }
}
