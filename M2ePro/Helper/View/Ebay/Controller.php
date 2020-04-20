<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Ebay;

/**
 * Class \Ess\M2ePro\Helper\View\Ebay\Controller
 */
class Controller extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $activeRecordFactory;
    protected $ebayFactory;
    protected $moduleConfig;
    protected $localeDate;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->moduleConfig = $moduleConfig;
        $this->localeDate = $localeDate;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function addMessages(\Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        if ($this->getHelper('View\Ebay')->isInstallationWizardFinished()) {
            $feedbacksNotificationMode = $this->moduleConfig->getGroupValue(
                '/view/ebay/feedbacks/notification/',
                'mode'
            );

            !$feedbacksNotificationMode ||
            !$this->haveNewNegativeFeedbacks() ||
            $this->addFeedbackNotificationMessage($controller);

            $this->addTokenExpirationDateNotificationMessage($controller);
            $this->addSellApiTokenExpirationDateNotificationMessage($controller);
            $this->addMarketplacesNotUpdatedNotificationMessage($controller);
        }
    }

    //########################################

    private function addFeedbackNotificationMessage(\Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        $url = $controller->getUrl('*/ebay_account/index');

        $message = 'New Buyer negative Feedback was received. '
            .'Go to the <a href="%url%" target="blank" class="external-link">Feedback Page</a>.';
        $message = $this->getHelper('Module\Translation')->__($message, $url);

        $controller->getMessageManager()->addNotice(
            $message,
            \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
        );
    }

    //########################################

    private function addTokenExpirationDateNotificationMessage(\Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        $tokenExpirationMessages = $this->getHelper('Data_Cache_Permanent')->getValue(__METHOD__);

        if ($tokenExpirationMessages === null) {
            $tokenExpirationMessages = [];

            $tempCollection = $this->ebayFactory->getObject('Account')->getCollection();

            $tempCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $tempCollection->getSelect()->columns(['id','title']);
            $tempCollection->getSelect()->columns('token_expired_date', 'second_table');
            $tempCollection->getSelect()->columns('sell_api_token_session', 'second_table');

            $currentTimeStamp = $this->getHelper('Data')->getCurrentTimezoneDate(true);

            foreach ($tempCollection->getData() as $accountData) {
                $tokenExpirationTimeStamp = strtotime($accountData['token_expired_date']);

                if ($tokenExpirationTimeStamp < $currentTimeStamp) {
                    $textToTranslate = <<<TEXT
Attention! The API token for "%account_title%" eBay Account is expired. The inventory and order synchronization
with eBay marketplace cannot be maintained until you grant M2E Pro the access token.<br>

Please, go to <i>%menu_label% > Configuration > eBay Account > <a href="%url%" target="_blank">General TAB</a></i>,
click Get Token. After you are redirected to the eBay website, sign into your seller account,
then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save</b> to apply the changes to your
Account Configuration.
TEXT;
                    if ($accountData['sell_api_token_session']) {
                        $textToTranslate = <<<TEXT
Attention! The Trading API token for "%account_title%" eBay Account is expired. The inventory and order synchronization
with eBay marketplace cannot be maintained until you grant M2E Pro the access token.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Trading API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save</b> to apply the changes to your
Account Configuration.
TEXT;
                    }
                    $tempMessage = $this->getHelper('Module\Translation')->__(
                        trim($textToTranslate),
                        $this->getHelper('Data')->escapeHtml($accountData['title']),
                        $this->getHelper('View\Ebay')->getMenuRootNodeLabel(),
                        $controller->getUrl('*/ebay_account/edit', ['id' => $accountData['id']])
                    );
                    $tokenExpirationMessages[] = [
                        'type' => 'error',
                        'message' => $tempMessage
                    ];

                    continue;
                }
                if (($currentTimeStamp + 60*60*24*10) >= $tokenExpirationTimeStamp) {
                    $textToTranslate = <<<TEXT
Attention! The API token for "%account_title%" eBay Account expires on %date%.
It needs to be renewed to maintain the inventory and order synchronization with eBay marketplace.<br>

Please, go to <i>%menu_label% > Configuration > eBay Account > <a href="%url%" target="_blank">General TAB</a></i>,
click Get Token. After you are redirected to the eBay website, sign into your seller account,
then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save</b> to apply the changes to your
Account Configuration.
TEXT;
                    if ($accountData['sell_api_token_session']) {
                        $textToTranslate = <<<TEXT
Attention! The Trading API token for "%account_title%" eBay Account expires on %date%.
It needs to be renewed to maintain the inventory and order synchronization with eBay marketplace.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Trading API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save</b> to apply the changes to your
Account Configuration.
TEXT;
                    }
                    $expirationDate = $this->localeDate->date(strtotime($accountData['token_expired_date']));
                    $expirationDate = $this->localeDate->formatDateTime(
                        $expirationDate,
                        \IntlDateFormatter::MEDIUM,
                        \IntlDateFormatter::SHORT
                    );

                    $tempMessage = $this->getHelper('Module\Translation')->__(
                        trim($textToTranslate),
                        $this->getHelper('Data')->escapeHtml($accountData['title']),
                        $expirationDate,
                        $this->getHelper('View\Ebay')->getMenuRootNodeLabel(),
                        $controller->getUrl('*/ebay_account/edit', ['id' => $accountData['id']])
                    );

                    $tokenExpirationMessages[] = [
                        'type' => 'notice',
                        'message' => $tempMessage
                    ];

                    continue;
                }
            }

            $this->getHelper('Data_Cache_Permanent')->setValue(
                __METHOD__,
                $tokenExpirationMessages,
                ['account','ebay'],
                60*60*24
            );
        }

        foreach ($tokenExpirationMessages as $messageData) {
            $method = 'add' . ucfirst($messageData['type']);
            $controller->getMessageManager()->$method(
                $messageData['message'],
                \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
            );
        }
    }

    private function addSellApiTokenExpirationDateNotificationMessage(\Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        $sellApiTokenExpirationMessages = $this->getHelper('Data_Cache_Permanent')->getValue(
            'ebay_accounts_sell_api_token_expiration_messages'
        );

        if ($sellApiTokenExpirationMessages === null) {

            $sellApiTokenExpirationMessages = [];

            /* @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $tempCollection*/
            $tempCollection = $this->ebayFactory->getObject('Account')->getCollection();

            $tempCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $tempCollection->getSelect()->columns(['id', 'title']);
            $tempCollection->getSelect()->columns('token_expired_date', 'second_table');
            $tempCollection->getSelect()->columns('sell_api_token_expired_date', 'second_table');

            $currentTimeStamp = $this->getHelper('Data')->getCurrentTimezoneDate(true);

            foreach ($tempCollection->getData() as $accountData) {

                $sellApiTokenExpirationTimeStamp = strtotime($accountData['sell_api_token_expired_date']);

                if ($sellApiTokenExpirationTimeStamp <= 0) {
                    continue;
                }

                if ($sellApiTokenExpirationTimeStamp < $currentTimeStamp) {
                    $textToTranslate = <<<TEXT
Attention! The Sell API token for "%account_title%" eBay Account is expired. The inventory and order synchronization
with eBay marketplace cannot be maintained until you grant M2E Pro the access token.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Sell API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save</b> to apply the changes to your
Account Configuration.
TEXT;
                    $tempMessage = $this->getHelper('Module\Translation')->__(
                        trim($textToTranslate),
                        $this->getHelper('Data')->escapeHtml($accountData['title']),
                        $this->getHelper('View_Ebay')->getMenuRootNodeLabel(),
                        $controller->getUrl('*/ebay_account/edit', ['id' => $accountData['id']])
                    );
                    $sellApiTokenExpirationMessages[] = [
                        'type' => 'error',
                        'message' => $tempMessage
                    ];

                    continue;
                }

                if (($currentTimeStamp + 60*60*24*10) >= $sellApiTokenExpirationTimeStamp) {
                    $textToTranslate = <<<TEXT
Attention! The Sell API token for "%account_title%" eBay Account expires on %date%.
It needs to be renewed to maintain the inventory and order synchronization with eBay marketplace.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Sell API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save</b> to apply the changes to your
Account Configuration.
TEXT;
                    $expirationDate = $this->localeDate->date(strtotime($accountData['token_expired_date']));
                    $expirationDate = $this->localeDate->formatDateTime(
                        $expirationDate,
                        \IntlDateFormatter::MEDIUM,
                        \IntlDateFormatter::SHORT
                    );

                    $tempMessage = $this->getHelper('Module\Translation')->__(
                        trim($textToTranslate),
                        $this->getHelper('Data')->escapeHtml($accountData['title']),
                        $expirationDate,
                        $this->getHelper('View_Ebay')->getMenuRootNodeLabel(),
                        $controller->getUrl('*/ebay_account/edit', ['id' => $accountData['id']])
                    );

                    $sellApiTokenExpirationMessages[] = [
                        'type' => 'notice',
                        'message' => $tempMessage
                    ];

                    continue;
                }
            }

            $this->getHelper('Data_Cache_Permanent')->setValue(
                'ebay_accounts_sell_api_token_expiration_messages',
                $sellApiTokenExpirationMessages,
                ['account', 'ebay'],
                60*60*24
            );
        }

        foreach ($sellApiTokenExpirationMessages as $messageData) {
            $method = 'add' . ucfirst($messageData['type']);
            $controller->getMessageManager()->$method(
                $messageData['message'],
                \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
            );
        }
    }

    private function addMarketplacesNotUpdatedNotificationMessage(
        \Ess\M2ePro\Controller\Adminhtml\Base $controller
    ) {
        $outdatedMarketplaces = $this->getHelper('Data_Cache_Permanent')->getValue(__METHOD__);

        if ($outdatedMarketplaces === null) {
            $marketplacesCollection = $this->ebayFactory->getObject('Marketplace')->getCollection();

            $resource = $marketplacesCollection->getResource();
            $dictionaryTable = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');

            $rows = $resource->getConnection()->select()->from($dictionaryTable, 'marketplace_id')
                ->where('client_details_last_update_date IS NOT NULL')
                ->where('server_details_last_update_date IS NOT NULL')
                ->where('client_details_last_update_date < server_details_last_update_date')
                ->query();

            $ids = [];
            foreach ($rows as $row) {
                $ids[] = $row['marketplace_id'];
            }

            $marketplacesCollection
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
                ['ebay','marketplace'],
                60*60*24
            );
        }

        if (count($outdatedMarketplaces) <= 0) {
            return;
        }

        $message = '%marketplace_title% data was changed on eBay. ' .
            'You need to resynchronize it for the proper Extension work. '.
            'Please, go to <a href="%url%" target="_blank">Marketplaces</a> and press an <b>Update All Now</b> button.';

        $controller->getMessageManager()->addNotice($this->getHelper('Module\Translation')->__(
            $message,
            implode(', ', $outdatedMarketplaces),
            $controller->getUrl('*/ebay_marketplace')
        ), \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP);
    }

    //########################################

    private function haveNewNegativeFeedbacks()
    {
        $configGroup = '/view/ebay/feedbacks/notification/';

        $lastCheckDate = $this->moduleConfig->getGroupValue($configGroup, 'last_check');

        if ($lastCheckDate === null) {
            $this->moduleConfig->setGroupValue(
                $configGroup,
                'last_check',
                $this->getHelper('Data')->getCurrentGmtDate()
            );
            return false;
        }

        $collection = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection()
                            ->addFieldToFilter('buyer_feedback_date', ['gt' => $lastCheckDate])
                            ->addFieldToFilter('buyer_feedback_type', \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEGATIVE);

        if ($collection->getSize() > 0) {
            $this->moduleConfig->setGroupValue(
                $configGroup,
                'last_check',
                $this->getHelper('Data')->getCurrentGmtDate()
            );
            return true;
        }

        return false;
    }

    //########################################
}
