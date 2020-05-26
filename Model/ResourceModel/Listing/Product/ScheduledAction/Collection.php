<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    /** how much time should pass to increase priority value by 1 */
    const SECONDS_TO_INCREMENT_PRIORITY = 30;

    /** @var null|string */
    protected $componentMode = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Listing\Product\ScheduledAction',
            'Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction'
        );
    }

    //########################################

    /**
     * @param $componentMode
     * @return $this
     */
    public function setComponentMode($componentMode)
    {
        $this->componentMode = $componentMode;
        return $this;
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getComponentMode()
    {
        if ($this->componentMode === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Component mode is not set.');
        }

        return $this->componentMode;
    }

    /**
     * @param int $priority
     * @param int $actionType
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getScheduledActionsPreparedCollection($priority, $actionType)
    {
        $this->getSelect()->joinLeft(
            ['lp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()],
            'main_table.listing_product_id = lp.id'
        );
        $this->getSelect()->joinLeft(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'lp.listing_id = l.id'
        );
        $this->getSelect()->joinLeft(
            ['pl' => $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable()],
            'pl.object_id = main_table.listing_product_id AND model_name = \'Listing_Product\''
        );

        $this->addFieldToFilter('component', $this->getComponentMode());
        $this->addFieldToFilter('pl.id', ['null' => true]);
        $this->addFieldToFilter('main_table.action_type', $actionType);

        $now = $this->getHelper('Data')->getCurrentGmtDate();
        $this->getSelect()->reset(\Zend_Db_Select::COLUMNS)
            ->columns(
                [
                    'id'                 => 'main_table.id',
                    'listing_product_id' => 'main_table.listing_product_id',
                    'account_id'         => 'l.account_id',
                    'action_type'        => 'main_table.action_type',
                    'tag'                => new \Zend_Db_Expr('NULL'),
                    'additional_data'    => 'main_table.additional_data',
                    'coefficient'        => new \Zend_Db_Expr(
                        "{$priority} +
                        (time_to_sec(timediff('{$now}', main_table.create_date)) / "
                        . self::SECONDS_TO_INCREMENT_PRIORITY . ")"
                    ),
                    'create_date'        => 'create_date',
                ]
            );

        return $this;
    }

    /**
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function joinAccountTable()
    {
        $componentMode = ucfirst($this->getComponentMode());
        $this->getSelect()->joinLeft(
            [
                'account' => $this
                    ->activeRecordFactory
                    ->getObject("{$componentMode}_Account")
                    ->getResource()
                    ->getMainTable()
            ],
            'l.account_id = account.account_id'
        );

        return $this;
    }

    /**
     * @param string $tag
     * @param bool $canBeEmpty
     * @return $this
     */
    public function addTagFilter($tag, $canBeEmpty = false)
    {
        $whereExpression = "main_table.tag LIKE '%/{$tag}/%'";
        if ($canBeEmpty) {
            $whereExpression .= " OR main_table.tag IS NULL OR main_table.tag = ''";
        }

        $this->getSelect()->where($whereExpression);
        return $this;
    }

    /**
     * @param \Zend_Db_Expr $expression
     * @return $this
     */
    public function addFilteredTagColumnToSelect(\Zend_Db_Expr $expression)
    {
        $this->getSelect()->columns(array('filtered_tag' => $expression));
        return $this;
    }

    //########################################

    /**
     * @param $secondsInterval
     * @return $this
     * @throws \Exception
     */
    public function addCreatedBeforeFilter($secondsInterval)
    {
        $interval = new \DateTime('now', new \DateTimeZone('UTC'));
        $interval->modify("-{$secondsInterval} seconds");

        $this->addFieldToFilter('main_table.create_date', ['lt' => $interval->format('Y-m-d H:i:s')]);
        return $this;
    }

    //########################################
}
