<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Indexes;

/**
 * Class \Ess\M2ePro\Observer\Indexes\Disable
 */
class Disable extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $productIndex;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product\Index $productIndex,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->productIndex = $productIndex;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        if ($this->getHelper('Magento')->isMSISupportingVersion()) {
            return;
        }

        if (!$this->productIndex->isIndexManagementEnabled()) {
            return;
        }

        foreach ($this->productIndex->getIndexes() as $code) {
            if ($this->productIndex->disableReindex($code)) {
                $this->productIndex->rememberDisabledIndex($code);
            }
        }
    }

    //########################################
}
