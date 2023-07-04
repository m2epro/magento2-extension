<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

class ExportCsvListingGrid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Magento\Backend\App\Response\Http\FileFactory */
    private $fileFactory;

    /** @var \Ess\M2ePro\Helper\Data\FileExport */
    private $fileExportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\FileExport $fileExportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Magento\Backend\App\Response\Http\FileFactory $fileFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->globalDataHelper = $globalDataHelper;
        $this->fileFactory = $fileFactory;
        $this->fileExportHelper = $fileExportHelper;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $id);

        $this->globalDataHelper->setValue('view_listing', $listing);

        $fileName = $this->fileExportHelper->generateExportFileName((string)$listing->getTitle());
        $content = $this->_view->getLayout()
                               ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Walmart\Grid::class)
                               ->getCsvFile();

        return $this->fileFactory->create($fileName, $content, 'var');
    }
}
