<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Registry;

/**
 * Class \Ess\M2ePro\Model\Registry\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setValue($key, $value)
    {
        is_array($value) && $value = $this->getHelper('Data')->jsonEncode($value);

        $registryModel = $this->loadByKey($key);
        $registryModel->setData('value', $value);
        $registryModel->save();

        return true;
    }

    public function getValue($key)
    {
        return $this->loadByKey($key)->getData('value');
    }

    public function getValueFromJson($key)
    {
        $registryModel = $this->loadByKey($key);
        return !$registryModel->getId()
            ? []
            : $this->getHelper('Data')->jsonDecode($registryModel->getData('value'));
    }

    public function deleteValue($key)
    {
        $registryModel = $this->loadByKey($key);
        if ($registryModel->getId()) {
            $registryModel->delete();
        }
    }

    //########################################

    private function loadByKey($key)
    {
        $registryModel = $this->activeRecordFactory->getObject('Registry');
        $registryModel->getResource()->load($registryModel, $key, 'key');

        if (!$registryModel->getId()) {
            $registryModel->setData('key', $key);
        }

        return $registryModel;
    }

    //########################################
}
