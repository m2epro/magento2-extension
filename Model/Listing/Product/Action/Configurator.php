<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Action;

abstract class Configurator extends \Ess\M2ePro\Model\AbstractModel
{
    const MODE_FULL    = 'full';
    const MODE_PARTIAL = 'partial';

    //########################################

    protected $mode = self::MODE_FULL;

    protected $allowedDataTypes = array();

    protected $params = array();

    //########################################

    public function getAllModes()
    {
        return array(
            self::MODE_FULL,
            self::MODE_PARTIAL,
        );
    }

    //########################################

    /**
     * @param string $mode
     * @return $this
     */
    public function setMode($mode)
    {
        if (!in_array($mode, $this->getAllModes())) {
            throw new \InvalidArgumentException('Mode is invalid.');
        }

        $this->mode = $mode;
        $this->allowedDataTypes = array();

        return $this;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    //########################################

    /**
     * @return bool
     */
    public function isFullMode()
    {
        return $this->mode == self::MODE_FULL;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
     */
    public function setFullMode()
    {
        return $this->setMode(self::MODE_FULL);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isPartialMode()
    {
        return $this->mode == self::MODE_PARTIAL;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
     */
    public function setPartialMode()
    {
        return $this->setMode(self::MODE_PARTIAL);
    }

    //########################################

    abstract public function getAllDataTypes();

    //########################################

    /**
     * @return bool
     */
    public function isAllAllowed()
    {
        if ($this->isFullMode()) {
            return true;
        }

        return !array_diff($this->getAllDataTypes(), $this->getAllowedDataTypes());
    }

    /**
     * @return array
     */
    public function getAllowedDataTypes()
    {
        if ($this->isFullMode()) {
            return $this->getAllDataTypes();
        }

        return $this->allowedDataTypes;
    }

    //########################################

    public function isAllowed($dataType)
    {
        $this->validateDataType($dataType);

        if ($this->isFullMode()) {
            return true;
        }

        return in_array($dataType, $this->allowedDataTypes);
    }

    public function allow($dataType)
    {
        $this->validateDataType($dataType);

        if ($this->isAllowed($dataType)) {
            return $this;
        }

        $this->allowedDataTypes[] = $dataType;
        return $this;
    }

    public function disallow($dataType)
    {
        $this->validateDataType($dataType);

        if (!$this->isAllowed($dataType)) {
            return $this;
        }

        if ($this->isFullMode()) {
            $this->setPartialMode();
            $this->allowedDataTypes = array_diff($this->getAllDataTypes(), array($dataType));

            return $this;
        }

        $this->allowedDataTypes = array_diff($this->allowedDataTypes, array($dataType));
        return $this;
    }

    //########################################

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator
     * @return bool
     */
    public function isDataConsists(\Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator)
    {
        if ($this->isAllAllowed()) {
            return true;
        }

        if ($configurator->isAllAllowed()) {
            return false;
        }

        return !array_diff($configurator->getAllowedDataTypes(), $this->getAllowedDataTypes());
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator
     * @return bool
     */
    public function isParamsConsists(\Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator)
    {
        return !array_diff_assoc($configurator->getParams(), $this->getParams());
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator
     * @return $this
     */
    public function mergeData(\Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator)
    {
        if ($this->isAllAllowed()) {
            return $this;
        }

        if ($configurator->isAllAllowed()) {
            $this->setFullMode();
            return $this;
        }

        if (!$this->isPartialMode()) {
            $this->setPartialMode();
        }

        $this->allowedDataTypes = array_unique(array_merge(
            $this->getAllowedDataTypes(), $configurator->getAllowedDataTypes()
        ));

        return $this;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator
     * @return $this
     */
    public function mergeParams(\Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator)
    {
        $this->params = array_unique(array_merge(
            $this->getParams(), $configurator->getParams()
        ));

        return $this;
    }

    //########################################

    /**
     * @return array
     */
    public function getSerializedData()
    {
        return array(
            'mode'               => $this->mode,
            'allowed_data_types' => $this->allowedDataTypes,
            'params'             => $this->params,
        );
    }

    /**
     * @param array $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setUnserializedData(array $data)
    {
        if (!empty($data['mode'])) {
            if (!in_array($data['mode'], $this->getAllModes())) {
                throw new \InvalidArgumentException('Mode is invalid.');
            }

            $this->mode = $data['mode'];
        }

        if (!empty($data['allowed_data_types'])) {
            if (!is_array($data['allowed_data_types']) ||
                array_diff($data['allowed_data_types'], $this->getAllDataTypes())
            ) {
                throw new \InvalidArgumentException('Allowed data types are invalid.');
            }

            $this->allowedDataTypes = $data['allowed_data_types'];
        }

        if (!empty($data['params'])) {
            if (!is_array($data['params'])) {
                throw new \InvalidArgumentException('Params has invalid format.');
            }

            $this->params = $data['params'];
        }

        return $this;
    }

    //########################################

    protected function validateDataType($dataType)
    {
        if (!in_array($dataType, $this->getAllDataTypes())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Data type is invalid');
        }
    }

    //########################################
}