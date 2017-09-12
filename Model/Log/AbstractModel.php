<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Model\Log;

use Ess\M2ePro\Model\Exception;

abstract class AbstractModel extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /**
     * The order of the values of log types' constants is important.
     * @see \Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::$actionsSortOrder
     * @see \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\View\Grouped\AbstractGrid::_prepareCollection()
     * @see \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\View\Grouped\AbstractGrid::_prepareCollection()
     */
    const TYPE_NOTICE   = 1;
    const TYPE_SUCCESS  = 2;
    const TYPE_WARNING  = 3;
    const TYPE_ERROR    = 4;

    const PRIORITY_HIGH    = 1;
    const PRIORITY_MEDIUM  = 2;
    const PRIORITY_LOW     = 3;

    protected $componentMode = NULL;

    protected $parentFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
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
        $this->parentFactory = $parentFactory;
        $this->resourceConnection = $resourceConnection;
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

    public function setComponentMode($mode)
    {
        $mode = strtolower((string)$mode);
        $mode && $this->componentMode = $mode;
        return $this;
    }

    public function getComponentMode()
    {
        return $this->componentMode;
    }

    //########################################

    public function getActionsTitles()
    {
        return $this->getHelper('Module\Log')->getActionsTitlesByClass(static::class);
    }

    //########################################
}