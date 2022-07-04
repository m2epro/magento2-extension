<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing;

abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory */
    protected $wrapperCollectionFactory;

    /** @var \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory */
    protected $customCollectionFactory;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /** @var \Ess\M2ePro\Helper\Data */
    protected $dataHelper;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory $wrapperCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\View $viewHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory $wrapperCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->config = $config;
        $this->wrapperCollectionFactory = $wrapperCollectionFactory;
        $this->customCollectionFactory = $customCollectionFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($resourceConnection, $viewHelper, $context, $backendHelper, $data);
    }

    abstract protected function getViewMode();

    abstract protected function getLogHash($type);

    abstract protected function getComponentMode();

    //#######################################

    protected function addMaxAllowedLogsCountExceededNotification($date)
    {
        $notification = $this->dataHelper->escapeJs($this->__(
            'Using a Grouped View Mode, the logs records which are not older than %date% are
            displayed here in order to prevent any possible Performance-related issues.',
            $this->_localeDate->formatDate($date, \IntlDateFormatter::MEDIUM, true)
        ));

        $this->js->add("M2ePro.formData.maxAllowedLogsCountExceededNotification = '{$notification}';");
    }

    protected function getMaxLastHandledRecordsCount()
    {
        return $this->config->getGroupValue(
            '/logs/grouped/',
            'max_records_count'
        );
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //#######################################
}
