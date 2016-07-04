<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization;

class LockItem extends \Ess\M2ePro\Model\LockItem
{
    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->setNick('synchronization');
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->addContentData('info_title',$title);
    }

    /**
     * @param int $percents
     */
    public function setPercents($percents)
    {
        (int)$percents < 0 && $percents = 0;
        (int)$percents > 100 && $percents = 100;
        $this->addContentData('info_percents',floor($percents));
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->addContentData('info_status',$status);
    }

    // ---------------------------------------

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->getContentData('info_title');
    }

    /**
     * @return int
     */
    public function getPercents()
    {
        return (int)$this->getContentData('info_percents');
    }

    /**
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getContentData('info_status');
    }

    //########################################
}