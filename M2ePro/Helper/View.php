<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

/**
 * Class \Ess\M2ePro\Helper\View
 */
class View extends \Ess\M2ePro\Helper\AbstractHelper
{
    const GENERAL_BLOCK_PATH = 'General';

    const LISTING_CREATION_MODE_FULL = 0;
    const LISTING_CREATION_MODE_LISTING_ONLY = 1;

    protected $activeRecordFactory;
    protected $urlBuilder;
    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->urlBuilder = $urlBuilder;
        $this->modelFactory = $modelFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    /**
     * @param string $viewNick
     * @return \Ess\M2ePro\Helper\View\Amazon|\Ess\M2ePro\Helper\View\Ebay
     */
    public function getViewHelper($viewNick = null)
    {
        if ($viewNick === null) {
            $viewNick = $this->getCurrentView();
        }

        switch ($viewNick) {
            case \Ess\M2ePro\Helper\View\Ebay::NICK:
                return $this->getHelper('View\Ebay');
            case \Ess\M2ePro\Helper\View\Amazon::NICK:
                return $this->getHelper('View\Amazon');
            case \Ess\M2ePro\Helper\View\Walmart::NICK:
                return $this->getHelper('View\Walmart');
        }

        return $this->getHelper('View\Amazon');
    }

    /**
     * @param string $viewNick
     * @return \Ess\M2ePro\Helper\View\Ebay\Controller|\Ess\M2ePro\Helper\View\Amazon\Controller
     */
    public function getControllerHelper($viewNick = null)
    {
        if ($viewNick === null) {
            $viewNick = $this->getCurrentView();
        }

        switch ($viewNick) {
            case \Ess\M2ePro\Helper\View\Ebay::NICK:
                return $this->getHelper('View_Ebay_Controller');
            case \Ess\M2ePro\Helper\View\Amazon::NICK:
                return $this->getHelper('View_Amazon_Controller');
            case \Ess\M2ePro\Helper\View\Walmart::NICK:
                return $this->getHelper('View_Walmart_Controller');
        }

        return $this->getHelper('View_Amazon_Controller');
    }

    //########################################

    public function getCurrentView()
    {
        $controllerName = $this->_getRequest()->getControllerName();

        if ($controllerName === null) {
            return null;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Ebay::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Ebay::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Amazon::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Amazon::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Walmart::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Walmart::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\ControlPanel::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\ControlPanel::NICK;
        }

        if (stripos($controllerName, 'system_config') !== false) {
            return \Ess\M2ePro\Helper\View\Configuration::NICK;
        }

        return null;
    }

    // ---------------------------------------

    public function isCurrentViewEbay()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Ebay::NICK;
    }

    public function isCurrentViewAmazon()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Amazon::NICK;
    }

    public function isCurrentViewWalmart()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Walmart::NICK;
    }

    public function isCurrentViewControlPanel()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\ControlPanel::NICK;
    }

    public function isCurrentViewConfiguration()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Configuration::NICK;
    }

    //########################################

    public function getUrl($row, $controller, $action, array $params = [])
    {
        $component = strtolower($row->getData('component_mode'));
        return $this->urlBuilder->getUrl("*/{$component}_{$controller}/{$action}", $params);
    }

    public function getModifiedLogMessage($logMessage)
    {
        $description = $this->getHelper('Module\Log')->decodeDescription($logMessage);

        preg_match_all('/[^(href=")]?(http|https)\:\/\/[a-z0-9\-\._\/+\?\&\%=;\(\)]+/i', $description, $matches);
        $matches = array_unique($matches[0]);

        foreach ($matches as &$url) {
            $url = trim($url, '.()[] ');
        }
        unset($url);

        foreach ($matches as $url) {
            $nestingLinks = 0;
            foreach ($matches as $value) {
                if (strpos($value, $url) !== false) {
                    $nestingLinks++;
                }
            }

            if ($nestingLinks > 1) {
                continue;
            }

            $description = str_replace($url, "<a target=\"_blank\" href=\"{$url}\">{$url}</a>", $description);
        }

        return $this->getHelper('Data')->escapeHtml($description, ['a'], ENT_NOQUOTES);
    }

    //########################################
}
