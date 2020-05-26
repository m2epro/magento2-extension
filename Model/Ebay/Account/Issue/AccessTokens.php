<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account\Issue;

use \Ess\M2ePro\Model\Issue\DataObject as Issue;
use \Magento\Framework\Message\MessageInterface as Message;

/**
 * Class \Ess\M2ePro\Model\Ebay\Account\Issue\AccessTokens
 */
class AccessTokens extends \Ess\M2ePro\Model\Issue\Locator\AbstractModel
{
    const CACHE_KEY = __CLASS__;

    protected $activeRecordFactory;
    protected $ebayFactory;
    protected $urlBuilder;

    protected $_localeDate;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Rule\Model\Condition\Context $context,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory         = $ebayFactory;
        $this->urlBuilder          = $urlBuilder;
        $this->_localeDate         = $context->getLocaleDate();

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getIssues()
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $messagesData = $this->getHelper('Data_Cache_Permanent')->getValue(self::CACHE_KEY);
        if (empty($messagesData)) {
            /** @var $accounts \Ess\M2ePro\Model\ResourceModel\Account\Collection */
            $accounts = $this->ebayFactory->getObject('Account')->getCollection();
            $accounts->addFieldToFilter('token_session', ['notnull' => true]);

            $messagesData = [];
            foreach ($accounts->getItems() as $account) {
                /** @var \Ess\M2ePro\Model\Account $account */
                $messagesData = array_merge(
                    $messagesData,
                    $this->getTradingApiTokenMessages($account),
                    $this->getSellApiTokenMessages($account)
                );
            }

            $this->getHelper('Data_Cache_Permanent')->setValue(
                self::CACHE_KEY,
                $messagesData,
                ['account','ebay'],
                60*60*24
            );
        }

        $issues = [];
        foreach ($messagesData as $messageData) {
            $issues[] = $this->modelFactory->getObject('Issue_DataObject', $messageData);
        }

        return $issues;
    }

    //########################################

    protected function getTradingApiTokenMessages(\Ess\M2ePro\Model\Account $account)
    {
        $currentTimeStamp = $this->getHelper('Data')->getCurrentTimezoneDate(true);
        $tokenExpirationTimeStamp = strtotime($account->getChildObject()->getTokenExpiredDate());

        if ($tokenExpirationTimeStamp < $currentTimeStamp) {
            $textToTranslate = <<<TEXT
Attention! The API token for "%account_title%" eBay Account is expired. The inventory and order synchronization
with eBay marketplace cannot be maintained until you grant M2E Pro the access token.<br>

Please, go to <i>%menu_label% > Configuration > Accounts > eBay Account > 
<a href="%url%" target="_blank">General TAB</a></i>,
click Get Token. After you are redirected to the eBay website, sign into your seller account,
then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save and Continue Edit</b> to apply the changes to your
Account Configuration.
TEXT;
            if ($account->getChildObject()->getSellApiTokenSession()) {
                $textToTranslate = <<<TEXT
Attention! The Trading API token for "%account_title%" eBay Account is expired. The inventory and order synchronization
with eBay marketplace cannot be maintained until you grant M2E Pro the access token.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Trading API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save and Continue Edit</b> to apply the changes to your
Account Configuration.
TEXT;
            }

            $tempTitle = $this->getHelper('Module\Translation')->__(
                'Attention! M2E Pro needs to be reauthorized: the API token for "%account_title%" eBay Account is
                expired. Please generate a new access token.',
                $this->getHelper('Data')->escapeHtml($account->getTitle())
            );
            $tempMessage = $this->getHelper('Module\Translation')->__(
                $textToTranslate,
                $this->getHelper('Data')->escapeHtml($account->getTitle()),
                $this->getHelper('View\Ebay')->getMenuRootNodeLabel(),
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                    'id' => $account->getId()
                ])
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_ERROR.__METHOD__
            );

            $messageUrl = $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                'id' => $account->getId(), '_query' => ['hash' => $editHash]
            ]);

            return [[
                Issue::KEY_TYPE  => Message::TYPE_ERROR,
                Issue::KEY_TITLE => $tempTitle,
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $messageUrl
            ]];
        }

        if (($currentTimeStamp + 60*60*24*10) >= $tokenExpirationTimeStamp) {
            $textToTranslate = <<<TEXT
Attention! The API token for "%account_title%" eBay Account expires on %date%.
It needs to be renewed to maintain the inventory and order synchronization with eBay marketplace.<br>

Please, go to <i>%menu_label% > Configuration > Accounts > eBay Account > 
<a href="%url%" target="_blank">General TAB</a></i>,
click Get Token. After you are redirected to the eBay website, sign into your seller account,
then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save and Continue Edit</b> to apply the changes to your
Account Configuration.
TEXT;
            if ($account->getChildObject()->getSellApiTokenSession()) {
                $textToTranslate = <<<TEXT
Attention! The Trading API token for "%account_title%" eBay Account expires on %date%.
It needs to be renewed to maintain the inventory and order synchronization with eBay marketplace.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Trading API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save and Continue Edit</b> to apply the changes to your
Account Configuration.
TEXT;
            }

            $tempTitle = $this->getHelper('Module\Translation')->__(
                'Attention! M2E Pro needs to be reauthorized: the API token for "%account_title%" eBay Account
                is to expire. Please generate a new access token.',
                $this->getHelper('Data')->escapeHtml($account->getTitle())
            );

            $tempMessage = $this->getHelper('Module\Translation')->__(
                $textToTranslate,
                $this->getHelper('Data')->escapeHtml($account->getTitle()),
                $this->_localeDate->formatDate($tokenExpirationTimeStamp, \IntlDateFormatter::MEDIUM, true),
                $this->getHelper('View\Ebay')->getMenuRootNodeLabel(),
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                    'id' => $account->getId()
                ])
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_NOTICE.__METHOD__
            );

            $messageUrl = $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                'id' => $account->getId(), '_query' => ['hash' => $editHash]
            ]);

            return [[
                Issue::KEY_TYPE  => Message::TYPE_NOTICE,
                Issue::KEY_TITLE => $tempTitle,
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $messageUrl
            ]];
        }

        return [];
    }

    protected function getSellApiTokenMessages(\Ess\M2ePro\Model\Account $account)
    {
        $currentTimeStamp = $this->getHelper('Data')->getCurrentTimezoneDate(true);
        $tokenExpirationTimeStamp = strtotime($account->getChildObject()->getSellApiTokenExpiredDate());

        if ($tokenExpirationTimeStamp <= 0) {
            return [];
        }

        if ($tokenExpirationTimeStamp < $currentTimeStamp) {
            $textToTranslate = <<<TEXT
Attention! The Sell API token for "%account_title%" eBay Account is expired. The inventory and order synchronization
with eBay marketplace cannot be maintained until you grant M2E Pro the access token.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Sell API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save and Continue Edit</b> to apply the changes to your
Account Configuration.
TEXT;

            $tempTitle = $this->getHelper('Module\Translation')->__(
                'Attention! M2E Pro needs to be reauthorized: the API token for "%account_title%" eBay Account is
                expired. Please generate a new access token.',
                $this->getHelper('Data')->escapeHtml($account->getTitle())
            );
            $tempMessage = $this->getHelper('Module\Translation')->__(
                $textToTranslate,
                $this->getHelper('Data')->escapeHtml($account->getTitle()),
                $this->getHelper('View\Ebay')->getMenuRootNodeLabel(),
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                    'id' => $account->getId()
                ])
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_ERROR.__METHOD__
            );

            $messageUrl = $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                'id' => $account->getId(), '_query' => ['hash' => $editHash]
            ]);

            return [[
                Issue::KEY_TYPE  => Message::TYPE_ERROR,
                Issue::KEY_TITLE => $tempTitle,
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $messageUrl
            ]];
        }

        if (($currentTimeStamp + 60*60*24*10) >= $tokenExpirationTimeStamp) {
            $textToTranslate = <<<TEXT
Attention! The Sell API token for "%account_title%" eBay Account expires on %date%.
It needs to be renewed to maintain the inventory and order synchronization with eBay marketplace.<br>

Please go to <i>%menu_label% > Configuration > Accounts > eBay Account > General >&nbsp
<a href="%url%" target="_blank">Sell API Details</a></i> and click Get Token. After you are redirected to the
eBay website, sign into your seller account, then click Agree to generate a new access token.<br>

<b>Note:</b> After the new eBay token is obtained, click <b>Save and Continue Edit</b> to apply the changes to your
Account Configuration.
TEXT;
            $tempTitle = $this->getHelper('Module\Translation')->__(
                'Attention! M2E Pro needs to be reauthorized: the API token for "%account_title%" eBay Account
                is to expire. Please generate a new access token.',
                $this->getHelper('Data')->escapeHtml($account->getTitle())
            );

            $tempMessage = $this->getHelper('Module\Translation')->__(
                $textToTranslate,
                $this->getHelper('Data')->escapeHtml($account->getTitle()),
                $this->_localeDate->formatDate($tokenExpirationTimeStamp, \IntlDateFormatter::MEDIUM, true),
                $this->getHelper('View\Ebay')->getMenuRootNodeLabel(),
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                    'id' => $account->getId()
                ])
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_NOTICE.__METHOD__
            );

            $messageUrl = $this->urlBuilder->getUrl('m2epro/ebay_account/edit', [
                'id' => $account->getId(), '_query' => ['hash' => $editHash]
            ]);

            return [[
                Issue::KEY_TYPE  => Message::TYPE_NOTICE,
                Issue::KEY_TITLE => $tempTitle,
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $messageUrl
            ]];
        }

        return [];
    }

    //########################################

    public function isNeedProcess()
    {
        return $this->getHelper('View\Ebay')->isInstallationWizardFinished() &&
            $this->getHelper('Component\Ebay')->isEnabled();
    }

    //########################################
}
