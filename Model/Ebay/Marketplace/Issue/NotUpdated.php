<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Marketplace\Issue;

use \Ess\M2ePro\Model\Issue\DataObject as Issue;
use \Magento\Framework\Message\MessageInterface as Message;

/**
 * Class \Ess\M2ePro\Model\Ebay\Marketplace\Issue\NotUpdated
 */
class NotUpdated extends \Ess\M2ePro\Model\Issue\Locator\AbstractModel
{
    const CACHE_KEY = __CLASS__;

    protected $ebayFactory;
    protected $urlBuilder;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayFactory        = $ebayFactory;
        $this->urlBuilder         = $urlBuilder;
        $this->resourceConnection = $resourceConnection;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getIssues()
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $outdatedMarketplaces = $this->getHelper('Data_Cache_Permanent')->getValue(self::CACHE_KEY);
        if (empty($outdatedMarketplaces)) {
            $tableName = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');

            $queryStmt = $this->resourceConnection->getConnection()
                ->select()
                ->from($tableName, ['marketplace_id', 'server_details_last_update_date'])
                ->where('client_details_last_update_date IS NOT NULL')
                ->where('server_details_last_update_date IS NOT NULL')
                ->where('client_details_last_update_date < server_details_last_update_date')
                ->query();

            $dictionaryData = [];
            while ($row = $queryStmt->fetch()) {
                $dictionaryData[(int)$row['marketplace_id']] = $row['server_details_last_update_date'];
            }

            $marketplacesCollection = $this->ebayFactory->getObject('Marketplace')->getCollection()
                ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                ->addFieldToFilter('id', ['in' => array_keys($dictionaryData)])
                ->setOrder('sorder', 'ASC');

            $outdatedMarketplaces = [];
            foreach ($marketplacesCollection as $marketplace) {
                /** @var $marketplace \Ess\M2ePro\Model\Marketplace */
                $outdatedMarketplaces[$marketplace->getTitle()] = $dictionaryData[$marketplace->getId()];
            }

            $this->getHelper('Data_Cache_Permanent')->setValue(
                self::CACHE_KEY,
                $outdatedMarketplaces,
                ['ebay', 'marketplace'],
                60*60*24
            );
        }

        if (empty($outdatedMarketplaces)) {
            return [];
        }

        $tempTitle = $this->getHelper('Module\Translation')->__(
            'M2E Pro requires action: eBay marketplace data needs to be synchronized.
            Please update eBay marketplaces.'
        );
        $textToTranslate = <<<TEXT
%marketplace_title% data was changed on eBay. You need to resynchronize the marketplace(s) to correctly
associate your products with eBay catalog.<br>
Please go to eBay Integration > Configuration > 
<a href="%url%" target="_blank">Marketplaces</a> and press <b>Update All Now</b>.
TEXT;

        $tempMessage = $this->getHelper('Module\Translation')->__(
            $textToTranslate,
            implode(', ', array_keys($outdatedMarketplaces)),
            $this->urlBuilder->getUrl('m2epro/ebay_marketplace/index')
        );

        $editHash = sha1(self::CACHE_KEY . $this->getHelper('Data')->jsonEncode($outdatedMarketplaces));
        $messageUrl = $this->urlBuilder->getUrl(
            'm2epro/ebay_marketplace/index',
            ['_query' => ['hash' => $editHash]]
        );

        return [
            $this->modelFactory->getObject('Issue_DataObject', [
                Issue::KEY_TYPE  => Message::TYPE_NOTICE,
                Issue::KEY_TITLE => $tempTitle,
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $messageUrl
            ])
        ];
    }

    //########################################

    public function isNeedProcess()
    {
        return $this->getHelper('View\Ebay')->isInstallationWizardFinished() &&
            $this->getHelper('Component\Ebay')->isEnabled();
    }

    //########################################
}
