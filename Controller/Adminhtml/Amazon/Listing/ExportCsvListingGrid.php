<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class ExportCsvListingGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Data\FileExport */
    private $fileExportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\FileExport $fileExportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->globalDataHelper = $globalDataHelper;
        $this->fileExportHelper = $fileExportHelper;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $id);

        $this->globalDataHelper->setValue('view_listing', $listing);

        $gridName = (string)$listing->getTitle();

        $content = $this->_view->getLayout()
                               ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Amazon\Grid::class)
                               ->getCsv();

        return $this->fileExportHelper->createFile($gridName, $content);
    }
}
