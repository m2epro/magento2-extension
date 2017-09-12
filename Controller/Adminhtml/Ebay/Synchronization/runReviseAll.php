<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;
use Ess\M2ePro\Controller\Adminhtml\Context;

class RunReviseAll extends Settings
{
    /** @var  \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    protected $localeDate;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        Context $context
    )
    {
        $this->localeDate = $localeDate;
        parent::__construct($ebayFactory, $context);
    }

    //########################################

    public function execute()
    {
        $startDate = $this->getHelper('Data')->getCurrentGmtDate();

        $synchConfig = $this->modelFactory->getObject('Config\Manager\Synchronization');

        $synchConfig->setGroupValue(
            '/ebay/templates/synchronization/revise/total/', 'start_date', $startDate
        );
        $synchConfig->setGroupValue(
            '/ebay/templates/synchronization/revise/total/', 'last_listing_product_id', 0
        );

        $this->setJsonContent(array(
            'start_date' => $this->localeDate->formatDate($startDate, \IntlDateFormatter::MEDIUM)
        ));
    }

    //########################################
}