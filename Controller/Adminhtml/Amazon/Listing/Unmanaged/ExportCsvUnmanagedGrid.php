<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Unmanaged;

class ExportCsvUnmanagedGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Magento\Backend\App\Response\Http\FileFactory */
    private $fileFactory;

    /** @var \Ess\M2ePro\Helper\Data\FileExport */
    private $fileExportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\FileExport $fileExportHelper,
        \Magento\Backend\App\Response\Http\FileFactory $fileFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->fileFactory = $fileFactory;
        $this->fileExportHelper = $fileExportHelper;
    }

    public function execute()
    {
        $fileName = $this->fileExportHelper->generateExportFileName(\Ess\M2ePro\Helper\Data\FileExport::UNMANAGED_GRID);
        $content = $this->_view->getLayout()
                               ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Unmanaged\Grid::class)
                               ->getCsvFile();

        return $this->fileFactory->create($fileName, $content, 'var');
    }
}
