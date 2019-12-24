<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Walmart;

/**
 * Class \Ess\M2ePro\Helper\View\Walmart\Controller
 */
class Controller extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    protected $resourceConnection;
    protected $walmartFactory;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->walmartFactory = $walmartFactory;

        parent::__construct($helperFactory, $context);
    }

    public function addMessages(\Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        if ($this->getHelper('View\Walmart')->isInstallationWizardFinished()) {
            $this->addMarketplacesNotUpdatedNotificationMessage($controller);
        }
    }

    //########################################

    private function addMarketplacesNotUpdatedNotificationMessage(
        \Ess\M2ePro\Controller\Adminhtml\Base $controller
    ) {
        $outdatedMarketplaces = $this->getHelper('Data_Cache_Permanent')->getValue(__METHOD__);

        if ($outdatedMarketplaces === null) {
            $readConn = $this->resourceConnection->getConnection();

            $dictionaryTable = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_walmart_dictionary_marketplace');

            $rows = $readConn->select()->from($dictionaryTable, 'marketplace_id')
                             ->where('client_details_last_update_date IS NOT NULL')
                             ->where('server_details_last_update_date IS NOT NULL')
                             ->where('client_details_last_update_date < server_details_last_update_date')
                             ->query();

            $ids = [];
            foreach ($rows as $row) {
                $ids[] = $row['marketplace_id'];
            }

            $marketplacesCollection = $this->walmartFactory->getObject('Marketplace')->getCollection()
                ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                ->addFieldToFilter('id', ['in' => $ids])
                ->setOrder('sorder', 'ASC');

            $outdatedMarketplaces = [];
            /** @var $marketplace \Ess\M2ePro\Model\Marketplace */
            foreach ($marketplacesCollection as $marketplace) {
                $outdatedMarketplaces[] = $marketplace->getTitle();
            }

            $this->getHelper('Data_Cache_Permanent')->setValue(
                __METHOD__,
                $outdatedMarketplaces,
                ['walmart','marketplace'],
                60*60*24
            );
        }

        if (count($outdatedMarketplaces) <= 0) {
            return;
        }

        $message = '%marketplace_title% data was changed on Walmart. ' .
            'You need to resynchronize it for the proper Extension work. '.
            'Please, go to <a href="%url%" target="_blank">Marketplaces</a> and press an <b>Update All Now</b> button.';

        $controller->getMessageManager()->addNotice($this->getHelper('Module\Translation')->__(
            $message,
            implode(', ', $outdatedMarketplaces),
            $controller->getUrl('*/walmart_marketplace')
        ), \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP);
    }

    //########################################
}
