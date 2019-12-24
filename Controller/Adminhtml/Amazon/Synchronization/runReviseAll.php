<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization\RunReviseAll
 */
class RunReviseAll extends Settings
{
    /** @var  \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    protected $localeDate;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    ) {
        $this->localeDate = $localeDate;
        parent::__construct($amazonFactory, $context);
    }

    //########################################

    public function execute()
    {
        $startDate = $this->getHelper('M2ePro')->getCurrentGmtDate();

        $synchConfig = $this->modelFactory->getObject('Config_Manager_Synchronization');

        $synchConfig->setGroupValue(
            '/amazon/templates/synchronization/revise/total/',
            'start_date',
            $startDate
        );
        $synchConfig->setGroupValue(
            '/amazon/templates/synchronization/revise/total/',
            'last_listing_product_id',
            0
        );

        $this->setJsonContent([
            'start_date' => $this->localeDate->formatDate($startDate, \IntlDateFormatter::MEDIUM)
        ]);
    }

    //########################################
}
