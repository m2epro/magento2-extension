<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

class RunSynchNow extends Marketplace
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $amazonHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Marketplace\Updater */
    protected $marketplaceUpdater;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Amazon\Marketplace\Updater $marketplaceUpdater,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->marketplaceUpdater = $marketplaceUpdater;
        $this->amazonHelper = $amazonHelper;
    }

    public function execute()
    {
        // @codingStandardsIgnoreLine
        session_write_close();

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace',
            (int)$this->getRequest()->getParam('marketplace_id')
        );

        $this->setJsonContent([
            'result' => $this->marketplaceUpdater->update($marketplace) ? 'success' : 'error'
        ]);

        return $this->getResult();
    }
}
