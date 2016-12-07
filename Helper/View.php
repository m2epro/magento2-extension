<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

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
    )
    {
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
        if (is_null($viewNick)) {
            $viewNick = $this->getCurrentView();
        }

        if ($viewNick == \Ess\M2ePro\Helper\View\Ebay::NICK) {
            return $this->getHelper('View\Ebay');
        }

        return $this->getHelper('View\Amazon');
    }

    /**
     * @param string $viewNick
     * @return \Ess\M2ePro\Helper\View\Amazon\Component|\Ess\M2ePro\Helper\View\Ebay\Component
     */
    public function getComponentHelper($viewNick = null)
    {
        if (is_null($viewNick)) {
            $viewNick = $this->getCurrentView();
        }

        if ($viewNick == \Ess\M2ePro\Helper\View\Ebay::NICK) {
            return $this->getHelper('View\Ebay\Component');
        }

        return $this->getHelper('View\Amazon\Component');
    }

    /**
     * @param string $viewNick
     * @return \Ess\M2ePro\Helper\View\Ebay\Controller|\Ess\M2ePro\Helper\View\Amazon\Controller
     */
    public function getControllerHelper($viewNick = null)
    {
        if (is_null($viewNick)) {
            $viewNick = $this->getCurrentView();
        }

        if ($viewNick == \Ess\M2ePro\Helper\View\Ebay::NICK) {
            return $this->getHelper('View\Ebay\Controller');
        }

        return $this->getHelper('View\Amazon\Controller');
    }

    //########################################

    public function getCurrentView()
    {
        $controllerName = $this->_getRequest()->getControllerName();

        if (is_null($controllerName)) {
            return NULL;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Ebay::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Ebay::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Amazon::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Amazon::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\ControlPanel::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\ControlPanel::NICK;
        }

        if (stripos($controllerName, 'system_config') !== false) {
            return \Ess\M2ePro\Helper\View\Configuration::NICK;
        }

        return NULL;
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

    public function isCurrentViewControlPanel()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\ControlPanel::NICK;
    }

    public function isCurrentViewConfiguration()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Configuration::NICK;
    }

    //########################################

    public function getUrl($row, $controller, $action, array $params = array())
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