<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Wizard extends \Ess\M2ePro\Helper\AbstractHelper
{
    const STATUS_NOT_STARTED = 0;
    const STATUS_ACTIVE      = 1;
    const STATUS_COMPLETED   = 2;
    const STATUS_SKIPPED     = 3;

    const KEY_VIEW     = 'view';
    const KEY_STATUS   = 'status';
    const KEY_STEP     = 'step';
    const KEY_PRIORITY = 'priority';
    const KEY_TYPE     = 'type';

    const TYPE_SIMPLE  = 0;
    const TYPE_BLOCKER = 1;

    private $cache = null;

    protected $activeRecordFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Wizard $wizard
     * @return string
     */
    public function getNick(\Ess\M2ePro\Model\Wizard $wizard)
    {
        return $wizard->getNick();
    }

    /**
     * Wizards Factory
     * @param string $nick
     * @return \Ess\M2ePro\Model\Wizard
     */
    public function getWizard($nick)
    {
        return $this->activeRecordFactory->getObject('Wizard\\'.ucfirst($nick));
    }

    //########################################

    public function isNotStarted($nick)
    {
        return $this->getStatus($nick) == self::STATUS_NOT_STARTED &&
               $this->getWizard($nick)->isActive();
    }

    public function isActive($nick)
    {
        return $this->getStatus($nick) == self::STATUS_ACTIVE &&
               $this->getWizard($nick)->isActive();
    }

    public function isCompleted($nick)
    {
        return $this->getStatus($nick) == self::STATUS_COMPLETED;
    }

    public function isSkipped($nick)
    {
        return $this->getStatus($nick) == self::STATUS_SKIPPED;
    }

    public function isFinished($nick)
    {
        return $this->isCompleted($nick) || $this->isSkipped($nick);
    }

    //########################################

    public function getView($nick)
    {
        return $this->getConfigValue($nick, self::KEY_VIEW);
    }

    public function getStatus($nick)
    {
        return $this->getConfigValue($nick, self::KEY_STATUS);
    }

    public function setStatus($nick, $status = self::STATUS_NOT_STARTED)
    {
        $this->setConfigValue($nick, self::KEY_STATUS, $status);
    }

    public function getStep($nick)
    {
        return $this->getConfigValue($nick, self::KEY_STEP);
    }

    public function setStep($nick, $step = NULL)
    {
        $this->setConfigValue($nick, self::KEY_STEP, $step);
    }

    public function getPriority($nick)
    {
        return $this->getConfigValue($nick, self::KEY_PRIORITY);
    }

    public function getType($nick)
    {
        return $this->getConfigValue($nick, self::KEY_TYPE);
    }

    //########################################

    /**
     * @param string $view
     * @return null|\Ess\M2ePro\Model\Wizard
     */
    public function getActiveWizard($view)
    {
        $wizards = $this->getAllWizards($view);

        /** @var $wizard \Ess\M2ePro\Model\Wizard */
        foreach ($wizards as $wizard) {
            if ($this->isNotStarted($this->getNick($wizard)) || $this->isActive($this->getNick($wizard))) {
                return $wizard;
            }
        }

        return null;
    }

    public function getActiveBlockerWizard($view)
    {
        $wizards = $this->getAllWizards($view);

        /** @var $wizard \Ess\M2ePro\Model\Wizard */
        foreach ($wizards as $wizard) {

            if ($this->getType($this->getNick($wizard)) != self::TYPE_BLOCKER) {
                continue;
            }

            if ($this->isNotStarted($this->getNick($wizard)) || $this->isActive($this->getNick($wizard))) {
                return $wizard;
            }
        }

        return null;
    }

    // ---------------------------------------

    private function getAllWizards($view)
    {
        (is_null($this->cache) || $this->getHelper('Module')->isDevelopmentEnvironment()) && $this->loadCache();

        $wizards = array();
        foreach ($this->cache as $nick => $wizard) {
            if ($wizard['view'] != '*' && $wizard['view'] != $view) {
                continue;
            }

            $wizards[] = $this->getWizard($nick);
        }

        return $wizards;
    }

    //########################################

    private function loadCache()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('m2epro_wizard');

        $this->cache = $connection->fetchAll(
            $connection->select()->from($tableName,'*')
        );

        usort($this->cache, function($a,$b) {

            if ($a['type'] != $b['type']) {
                return $a['type'] == \Ess\M2ePro\Helper\Module\Wizard::TYPE_BLOCKER ? - 1 : 1;
            }

            if ($a['priority'] == $b['priority']) {
                return 0;
            }

            return $a['priority'] > $b['priority'] ? 1 : -1;
        });

        foreach ($this->cache as $id => $wizard) {
            $this->cache[$wizard['nick']] = $wizard;
            unset($this->cache[$id]);
        }

        $this->getHelper('Data\Cache\Permanent')->setValue(
            'wizard',
            $this->getHelper('Data')->jsonEncode($this->cache),
            array('wizard'),
            60*60
        );
    }

    // ---------------------------------------

    private function getConfigValue($nick, $key)
    {
        $this->getHelper('Module')->isDevelopmentEnvironment() && $this->loadCache();

        if (!is_null($this->cache)) {
            return $this->cache[$nick][$key];
        }

        if (($this->cache = $this->getHelper('Data\Cache\Permanent')->getValue('wizard')) !== NULL) {
            $this->cache = $this->getHelper('Data')->jsonDecode($this->cache);
            return $this->cache[$nick][$key];
        }

        $this->loadCache();

        return $this->cache[$nick][$key];
    }

    private function setConfigValue($nick, $key, $value)
    {
        (is_null($this->cache) || $this->getHelper('Module')->isDevelopmentEnvironment()) && $this->loadCache();

        $this->cache[$nick][$key] = $value;

        $this->getHelper('Data\Cache\Permanent')->setValue(
            'wizard',
            $this->getHelper('Data')->jsonEncode($this->cache),
            array('wizard'),
            60*60
        );

        $connWrite = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('m2epro_wizard');

        $connWrite->update(
            $tableName,
            array($key => $value),
            array('nick = ?' => $nick)
        );

        return $this;
    }

    //########################################
}