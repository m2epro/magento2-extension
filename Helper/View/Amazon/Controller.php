<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Amazon;

class Controller extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    protected $resourceConnection;
    protected $amazonFactory;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->amazonFactory = $amazonFactory;

        parent::__construct($helperFactory, $context);
    }

    public function addMessages(\Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        if ($this->getHelper('View\Amazon')->isInstallationWizardFinished()) {
            $this->addMarketplacesNotUpdatedNotificationMessage($controller);
        }
    }

    //########################################

    private function addMarketplacesNotUpdatedNotificationMessage(
                                \Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        $outdatedMarketplaces = $this->getHelper('Data\Cache\Permanent')->getValue(__METHOD__);

        if ($outdatedMarketplaces === NULL) {

            $readConn = $this->resourceConnection->getConnection();

            $dictionaryTable = $this->resourceConnection->getTableName('m2epro_amazon_dictionary_marketplace');

            $rows = $readConn->select()->from($dictionaryTable,'marketplace_id')
                             ->where('client_details_last_update_date IS NOT NULL')
                             ->where('server_details_last_update_date IS NOT NULL')
                             ->where('client_details_last_update_date < server_details_last_update_date')
                             ->query();

            $ids = array();
            foreach ($rows as $row) {
                $ids[] = $row['marketplace_id'];
            }

            $marketplacesCollection = $this->amazonFactory->getObject('Marketplace')->getCollection()
                ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                ->addFieldToFilter('id',array('in' => $ids))
                ->setOrder('sorder','ASC');

            $outdatedMarketplaces = array();
            /* @var $marketplace \Ess\M2ePro\Model\Marketplace */
            foreach ($marketplacesCollection as $marketplace) {
                $outdatedMarketplaces[] = $marketplace->getTitle();
            }

            $this->getHelper('Data\Cache\Permanent')->setValue(__METHOD__,
                                                               $outdatedMarketplaces,
                                                               array('amazon','marketplace'),
                                                               60*60*24);
        }

        if (count($outdatedMarketplaces) <= 0) {
            return;
        }

        $message = '%marketplace_title% data was changed on Amazon. ' .
            'You need to resynchronize it for the proper Extension work. '.
            'Please, go to <a href="%url%" target="_blank">Marketplaces</a> and press an <b>Update All Now</b> button.';

        $controller->getMessageManager()->addNotice($this->getHelper('Module\Translation')->__(
            $message,
            implode(', ',$outdatedMarketplaces),
            $controller->getUrl('*/amazon_marketplace')
        ), \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP);
    }

    //########################################
}