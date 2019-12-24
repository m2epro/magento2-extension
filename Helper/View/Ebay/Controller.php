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
            $this->addMarketplacesNotUpdatedNotificationMessage($controller);
        }
    }

    //########################################

    private function addFeedbackNotificationMessage(\Ess\M2ePro\Controller\Adminhtml\Base $controller)
    {
        $url = $controller->getUrl('*/ebay_account/index');

        // M2ePro_TRANSLATIONS
        // New Buyer negative Feedback was received. Go to the <a href="%url%" target="blank">feedback Page</a>.
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

            $currentTimeStamp = $this->getHelper('Data')->getCurrentTimezoneDate(true);
            $format = $this->localeDate->getDateTimeFormat(\IntlDateFormatter::MEDIUM);

            foreach ($tempCollection->getData() as $accountData) {
                $tokenExpirationTimeStamp = strtotime($accountData['token_expired_date']);
// M2ePro_TRANSLATIONS
/*
The token for "%account_title%" eBay Account has been expired.<br/>
Please, go to %menu_label% > Configuration > eBay Account >
<a href="%url%" target="_blank">General TAB</a>, click on the Get Token button.
(You will be redirected to the eBay website.) Sign-in and press I Agree on eBay Page.
Do not forget to press Save button after returning back to Magento
 */
                $textToTranslate =
                    'The token for "%account_title%" eBay Account has been expired.<br/>'.
                    'Please, go to %menu_label% > Configuration > eBay Account >'.
                    '<a href="%url%" target="_blank" class="external-link">General TAB</a>'.
                    ', click on the Get Token Button.'.
                    '(You will be redirected to the eBay website.) Sign-in and press I Agree on eBay Page.'.
                    'Do not forget to press Save Button after returning back to Magento';

                if ($tokenExpirationTimeStamp < $currentTimeStamp) {
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
// M2ePro_TRANSLATIONS
/*
Attention! The token for "%account_title%" eBay Account will be expired soon ( %date% ).
<br/>Please, go to %menu_label% > Configuration > eBay Account >
<a href="%url%" target="_blank">General TAB</a>, click on the Get Token Button.
(You will be redirected to the eBay website.) Sign-in and press I Agree on eBay Page.
Do not forget to press Save Button after returning back to Magento
 */
                $textToTranslate =
                    'Attention! The token for "%account_title%" eBay Account will be expired soon ( %date% ).'.
                    '<br/>Please, go to %menu_label% > Configuration > eBay Account >'.
                    '<a href="%url%" target="_blank">General TAB</a>, click on the Get Token Button.'.
                    '(You will be redirected to the eBay website.) Sign-in and press I Agree on eBay Page.'.
                    'Do not forget to press Save Button after returning back to Magento';

                if (($currentTimeStamp + 60*60*24*10) >= $tokenExpirationTimeStamp) {
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
