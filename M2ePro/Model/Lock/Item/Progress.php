<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock\Item;

/**
 * Class \Ess\M2ePro\Model\Lock\Item\Progress
 */
class Progress extends \Ess\M2ePro\Model\AbstractModel
{
    const CONTENT_DATA_KEY = 'progress_data';

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager  */
    protected $lockItemManager;

    /** @var string */
    protected $progressNick;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager,
        $progressNick,
        array $data = []
    ) {
        $this->lockItemManager = $lockItemManager;
        $this->progressNick   = str_replace('/', '-', $progressNick);
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function isInProgress()
    {
        $contentData = $this->lockItemManager->getContentData();
        return isset($contentData[self::CONTENT_DATA_KEY][$this->progressNick]);
    }

    // ---------------------------------------

    public function start()
    {
        $contentData = $this->lockItemManager->getContentData();

        $contentData[self::CONTENT_DATA_KEY][$this->progressNick] = ['percentage' => 0];

        $this->lockItemManager->setContentData($contentData);

        return $this;
    }

    public function setPercentage($percentage)
    {
        $contentData = $this->lockItemManager->getContentData();

        $contentData[self::CONTENT_DATA_KEY][$this->progressNick]['percentage'] = $percentage;

        $this->lockItemManager->setContentData($contentData);

        return $this;
    }

    public function setDetails($args = [])
    {
        $contentData = $this->lockItemManager->getContentData();

        $contentData[self::CONTENT_DATA_KEY][$this->progressNick] = $args;

        $this->lockItemManager->setContentData($contentData);

        return $this;
    }

    public function stop()
    {
        $contentData = $this->lockItemManager->getContentData();

        unset($contentData[self::CONTENT_DATA_KEY][$this->progressNick]);

        $this->lockItemManager->setContentData($contentData);

        return $this;
    }

    //########################################
}
