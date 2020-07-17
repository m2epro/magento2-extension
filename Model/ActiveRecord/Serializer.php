<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Serializer
 */
class Serializer extends \Ess\M2ePro\Model\AbstractModel
{
    const SETTING_FIELD_TYPE_JSON          = 'json';
    const SETTING_FIELD_TYPE_SERIALIZATION = 'serialization';

    /** @var ActiveRecordAbstract */
    protected $model;

    //########################################

    public function setModel(ActiveRecordAbstract $model)
    {
        $this->model = $model;
        return $this;
    }

    //########################################

    /**
     * @param string $fieldName
     * @param string $encodeType
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSettings(
        $fieldName,
        $encodeType = self::SETTING_FIELD_TYPE_JSON
    ) {
        if (null === $this->model) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model was not set');
        }

        $settings = $this->model->getData((string)$fieldName);

        if ($settings === null) {
            return [];
        }

        if ($encodeType === self::SETTING_FIELD_TYPE_JSON) {
            $settings = $this->getHelper('Data')->jsonDecode($settings);
        } elseif ($encodeType === self::SETTING_FIELD_TYPE_SERIALIZATION) {
            $settings = $this->getHelper('Data')->phpUnserialize($settings);
        } else {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Encoding type "%encode_type%" is not supported.', $encodeType
            );
        }

        return !empty($settings) ? $settings : [];
    }

    /**
     * @param string $fieldName
     * @param string|array $settingNamePath
     * @param mixed $defaultValue
     * @param string $encodeType
     *
     * @return mixed|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSetting(
        $fieldName,
        $settingNamePath,
        $defaultValue = NULL,
        $encodeType = self::SETTING_FIELD_TYPE_JSON
    ) {
        if (empty($settingNamePath)) {
            return $defaultValue;
        }

        $settings = $this->getSettings($fieldName, $encodeType);

        !is_array($settingNamePath) && $settingNamePath = [$settingNamePath];

        foreach ($settingNamePath as $pathPart) {
            if (!isset($settings[$pathPart])) {
                return $defaultValue;
            }

            $settings = $settings[$pathPart];
        }

        if (is_numeric($settings)) {
            $settings = ctype_digit($settings) ? (int)$settings : $settings;
        }

        return $settings;
    }

    // ---------------------------------------

    /**
     * @param string $fieldName
     * @param array  $settings
     * @param string $encodeType
     *
     * @return ActiveRecordAbstract
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setSettings(
        $fieldName,
        array $settings = [],
        $encodeType = self::SETTING_FIELD_TYPE_JSON
    ) {
        if (null === $this->model) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model was not set');
        }

        if ($encodeType == self::SETTING_FIELD_TYPE_JSON) {
            $settings = $this->getHelper('Data')->jsonEncode($settings);
        } elseif ($encodeType == self::SETTING_FIELD_TYPE_SERIALIZATION) {
            $settings = $this->getHelper('Data')->phpSerialize($settings);
        } else {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Encoding type "%encode_type%" is not supported.', $encodeType
            );
        }

        $this->model->setData((string)$fieldName, $settings);
        return $this->model;
    }

    /**
     * @param string $fieldName
     * @param string|array $settingNamePath
     * @param mixed $settingValue
     * @param string $encodeType
     *
     * @return ActiveRecordAbstract
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setSetting(
        $fieldName,
        $settingNamePath,
        $settingValue,
        $encodeType = self::SETTING_FIELD_TYPE_JSON
    ) {
        if (empty($settingNamePath)) {
            return $this->model;
        }

        $settings = $this->getSettings($fieldName, $encodeType);
        $target = &$settings;

        !is_array($settingNamePath) && $settingNamePath = [$settingNamePath];

        $currentPathNumber = 0;
        $totalPartsNumber = count($settingNamePath);

        foreach ($settingNamePath as $pathPart) {
            $currentPathNumber++;

            if (!array_key_exists($pathPart, $settings) && $currentPathNumber != $totalPartsNumber) {
                $target[$pathPart] = [];
            }

            if ($currentPathNumber != $totalPartsNumber) {
                $target = &$target[$pathPart];
                continue;
            }

            $target[$pathPart] = $settingValue;
        }

        return $this->setSettings($fieldName, $settings, $encodeType);
    }

    //########################################
}
