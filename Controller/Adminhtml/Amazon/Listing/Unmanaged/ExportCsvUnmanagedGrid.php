<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Unmanaged;

class ExportCsvUnmanagedGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Helper\Data\FileExport */
    private $fileExportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\FileExport $fileExportHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->fileExportHelper = $fileExportHelper;
    }

    public function execute()
    {
        $gridName = \Ess\M2ePro\Helper\Data\FileExport::UNMANAGED_GRID;

        $content = $this->_view->getLayout()
                               ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Unmanaged\Grid::class)
                               ->getCsv();

        return $this->fileExportHelper->createFile($gridName, $content);
    }
}
