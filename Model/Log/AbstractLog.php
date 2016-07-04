<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Model\Log;

use Ess\M2ePro\Model\Exception;

class AbstractLog extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const TYPE_NOTICE   = 1;
    const TYPE_SUCCESS  = 2;
    const TYPE_WARNING  = 3;
    const TYPE_ERROR    = 4;

    const PRIORITY_HIGH    = 1;
    const PRIORITY_MEDIUM  = 2;
    const PRIORITY_LOW     = 3;

    protected $componentMode = NULL;

    protected $parentFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->parentFactory = $parentFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function setComponentMode($mode)
    {
        $mode = strtolower((string)$mode);
        $mode && $this->componentMode = $mode;
        return $this;
    }

    public function getComponentMode()
    {
        return $this->componentMode;
    }

    //########################################

    public function getNextActionId()
    {
        /** @var $connection \Magento\Framework\DB\Adapter\AdapterInterface */
        $connection = $this->resourceConnection->getConnection();

        $table = $this->resourceConnection->getTableName('m2epro_module_config');
        $groupConfig = '/logs/'.$this->getLastActionIdConfigKey().'/';

        $lastActionId = (int)$connection->select()
            ->from($table,'value')
            ->where('`group` = ?',$groupConfig)
            ->where('`key` = ?','last_action_id')
            ->query()->fetchColumn();

        $nextActionId = $lastActionId + 1;

        $connection->update(
            $table,
            array('value' => $nextActionId),
            array('`group` = ?' => $groupConfig, '`key` = ?' => 'last_action_id')
        );

        return $nextActionId;
    }

    /**
     * @return string
     */
    public function getLastActionIdConfigKey()
    {
        return 'general';
    }

    // ---------------------------------------

    public function getStatusByActionId($actionId)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('action_id', $actionId);

        $logRecords = $collection->getData();
        if (empty($logRecords)) {
            throw new Exception('Logs action id does not exists.');
        }

        $typesStatusesMap = array(
            self::TYPE_NOTICE  => \Ess\M2ePro\Helper\Data::STATUS_SUCCESS,
            self::TYPE_SUCCESS => \Ess\M2ePro\Helper\Data::STATUS_SUCCESS,
            self::TYPE_WARNING => \Ess\M2ePro\Helper\Data::STATUS_WARNING,
            self::TYPE_ERROR   => \Ess\M2ePro\Helper\Data::STATUS_ERROR,
        );

        $statuses = array();

        foreach ($logRecords as $logRecord) {
            $statuses[] = $typesStatusesMap[$logRecord['type']];
        }

        return $this->getHelper('Data')->getMainStatus($statuses);
    }

    // ---------------------------------------

    /**
     * @param string $string
     * @param array $params
     * @param array $links
     * @return string
     */
    public function encodeDescription($string, array $params = array(), array $links = array())
    {
        if (count($params) <= 0 && count($links) <= 0) {
            return $string;
        }

        $descriptionData = array(
            'string' => $string,
            'params' => $params,
            'links'  => $links
        );

        return json_encode($descriptionData);
    }

    /**
     * @param string $string
     * @return string
     */
    public function decodeDescription($string)
    {
        if (!is_string($string) || $string == '') {
            return '';
        }

        if ($string{0} != '{') {
            return $this->getHelper('Module\Translation')->__($string);
        }

        $descriptionData = json_decode($string,true);
        $string = $this->getHelper('Module\Translation')->__($descriptionData['string']);

        if (!empty($descriptionData['params'])) {
            $string = $this->addPlaceholdersToMessage($string, $descriptionData['params']);
        }

        if (!empty($descriptionData['links'])) {
            $string = $this->addLinksToMessage($string, $descriptionData['links']);
        }

        return $string;
    }

    // ---------------------------------------

    protected function addPlaceholdersToMessage($string, $params)
    {
        foreach ($params as $key=>$value) {

            if (isset($value{0}) && $value{0} == '{') {
                $tempValueArray = json_decode($value, true);
                is_array($tempValueArray) && $value = $this->decodeDescription($value);
            }

            if ($key{0} == '!') {
                $key = substr($key,1);
            } else {
                $value = $this->getHelper('Module\Translation')->__($value);
            }

            $string = str_replace('%'.$key.'%',$value,$string);
        }

        return $string;
    }

    protected function addLinksToMessage($string, $links)
    {
        $readMoreLinks = array();
        $resultString = $string;

        foreach ($links as $link) {
            preg_match('/!\w*_start!/', $resultString, $foundedStartMatches);

            if (count($foundedStartMatches) == 0) {
                $readMoreLinks[] = $link;
                continue;
            } else {

                $startPart = $foundedStartMatches[0];
                $endPart = str_replace('start', 'end', $startPart);

                $wasFoundEndMatches = strpos($resultString, $endPart);

                if ($wasFoundEndMatches !== false) {

                    $openLinkTag = '<a href="' . $link . '" target="_blank">';
                    $closeLinkTag = '</a>';

                    $resultString = str_replace($startPart, $openLinkTag, $resultString);
                    $resultString = str_replace($endPart, $closeLinkTag, $resultString);

                } else {
                    $readMoreLinks[] = $link;
                }
            }
        }

        if (count($readMoreLinks) > 0) {

            $translation = $this->getHelper('Module\Translation');

            foreach ($readMoreLinks as &$link) {
                $link = '<a href="' . $link . '" target="_blank">' . $translation->__('here') . '</a>';
            }

            $delimiter = $translation->__('or');
            $readMoreString = $translation->__('Details').' '.implode(' '.$delimiter.' ', $readMoreLinks).'.';

            $resultString .= ' ' . $readMoreString;
        }

        return $resultString;
    }

    //########################################

    protected function getActionTitleByClass($class, $type)
    {
        $reflectionClass = new \ReflectionClass ($class);
        $tempConstants = $reflectionClass->getConstants();

        foreach ($tempConstants as $key => $value) {
            if ($key == '_'.$type) {
                return $this->getHelper('Module\Translation')->__($key);
            }
        }

        return '';
    }

    protected function getActionsTitlesByClass($class, $prefix)
    {
        $reflectionClass = new \ReflectionClass ($class);
        $tempConstants = $reflectionClass->getConstants();

        $actionsNames = array();
        foreach ($tempConstants as $key => $value) {
            if (substr($key,0,strlen($prefix)) == $prefix) {
                $actionsNames[$key] = $value;
            }
        }

        $actionsValues = array();
        foreach ($actionsNames as $action => $valueAction) {
            foreach ($tempConstants as $key => $valueConstant) {
                if ($key == '_'.$action) {
                    $actionsValues[$valueAction] = $this->helperFactory
                        ->getObject('Module\Translation')->__($valueConstant);
                }
            }
        }

        return $actionsValues;
    }

    // ---------------------------------------

    protected function clearMessagesByTable($tableNameOrModelName, $columnName = NULL, $columnId = NULL)
    {
        $logsTable  = $this->activeRecordFactory->getObject($tableNameOrModelName)->getResource()->getMainTable();

        $where = array();
        if (!is_null($columnId)) {
            $where[$columnName.' = ?'] = $columnId;
        }

        if (!is_null($this->componentMode)) {
            $where['component_mode = ?'] = $this->componentMode;
        }

        $this->resourceConnection->getConnection('core_write')->delete($logsTable,$where);
    }

    //########################################
}