<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid
{
    protected $wrapperCollectionFactory;
    protected $customCollectionFactory;

    //#######################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\WrapperFactory $wrapperCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->wrapperCollectionFactory = $wrapperCollectionFactory;
        $this->customCollectionFactory = $customCollectionFactory;

        parent::__construct($resourceConnection, $context, $backendHelper, $data);
    }

    //#######################################

    abstract protected function getViewMode();

    abstract protected function getLogHash($type);

    abstract protected function getComponentMode();

    //#######################################

    protected function addMaxAllowedLogsCountExceededNotification($date)
    {
        $notification = $this->getHelper('Data')->escapeJs($this->__(
            'Using a Grouped View Mode, the logs records which are not older than %date% are
            displayed here in order to prevent any possible Performance-related issues.',
            $this->_localeDate->formatDate($date, \IntlDateFormatter::MEDIUM, true)
        ));

        $this->js->add("M2ePro.formData.maxAllowedLogsCountExceededNotification = '{$notification}';");
    }

    protected function getMaxLastHandledRecordsCount()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
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
