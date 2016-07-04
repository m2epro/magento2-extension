<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

class Index extends \Ess\M2ePro\Model\AbstractModel
{
    protected $indexerFactory;
    protected $indexers = [];

    //########################################

    public function __construct(
        \Magento\Framework\Indexer\IndexerInterfaceFactory $indexerFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->indexerFactory = $indexerFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Magento\Indexer\Model\Indexer
     */
    public function getIndexer($code)
    {
        if (isset($this->indexers[$code])) {
            return $this->indexers[$code];
        }

        return $this->indexers[$code] = $this->indexerFactory->create()->load($code);
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        return array(
            'cataloginventory_stock'
        );
    }

    //########################################

    public function disableReindex($code)
    {
        $indexer = $this->getIndexer($code);
        $mode = $indexer->getView()->getState()->getMode();

        if ($mode == \Magento\Framework\Mview\View\StateInterface::MODE_ENABLED) {
            return false;
        }

        //update by schedule
        $indexer->getView()
                ->getState()
                ->setMode(\Magento\Framework\Mview\View\StateInterface::MODE_ENABLED)
                ->save();

        return true;
    }

    public function enableReindex($code)
    {
        $indexer = $this->getIndexer($code);
        $mode = $indexer->getView()->getState()->getMode();

        if (!$mode) {
            return false;
        }

        if ($mode == \Magento\Framework\Mview\View\StateInterface::MODE_DISABLED) {
            return false;
        }

        $indexer->getView()
                ->getState()
                ->setMode(\Magento\Framework\Mview\View\StateInterface::MODE_DISABLED)
                ->save();

        return true;
    }

    // ---------------------------------------

    public function requireReindex($code)
    {
        return $this->getIndexer($code)->getStatus() === \Magento\Framework\Indexer\StateInterface::STATUS_INVALID;
    }

    public function executeReindex($code)
    {
        $indexer = $this->getIndexer($code);

        if ($indexer === false || $indexer->getStatus() == \Magento\Framework\Indexer\StateInterface::STATUS_WORKING) {
            return false;
        }

        $indexer->reindexAll();
        return true;
    }

    //########################################

    /**
     * @return bool
     */
    public function isIndexManagementEnabled()
    {
        return (bool)(int)$this->getHelper('Module')->getConfig()
                            ->getGroupValue('/product/index/', 'mode');
    }

    public function isDisabledIndex($code)
    {
        return (bool)(int)$this->getHelper('Module')->getConfig()
                            ->getGroupValue('/product/index/'.$code.'/', 'disabled');
    }

    // ---------------------------------------

    public function rememberDisabledIndex($code)
    {
        $this->getHelper('Module')->getConfig()
            ->setGroupValue('/product/index/'.$code.'/', 'disabled', 1);
    }

    public function forgetDisabledIndex($code)
    {
        $this->getHelper('Module')->getConfig()
            ->setGroupValue('/product/index/'.$code.'/', 'disabled', 0);
    }

    //########################################
}