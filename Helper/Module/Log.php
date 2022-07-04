<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Log
{
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper
    ) {
        $this->translationHelper = $translationHelper;
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $string
     * @param array $params
     * @param array $links
     *
     * @return string
     */
    public function encodeDescription(string $string, array $params = [], array $links = []): string
    {
        if (empty($params) && empty($links)) {
            return $string;
        }

        $descriptionData = [
            'string' => $string,
            'params' => $params,
            'links'  => $links,
        ];

        return json_encode($descriptionData);
    }

    /**
     * @param string $string
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function decodeDescription($string)
    {
        if (!is_string($string) || $string == '') {
            return '';
        }

        if ($string[0] !== '{') {
            return $this->translationHelper->__($string);
        }

        $descriptionData = json_decode($string, true);
        $string = $this->translationHelper->__($descriptionData['string']);

        if (!empty($descriptionData['params'])) {
            $string = $this->addPlaceholdersToMessage($string, $descriptionData['params']);
        }

        if (!empty($descriptionData['links'])) {
            $string = $this->addLinksToMessage($string, $descriptionData['links']);
        }

        return $string;
    }

    /**
     * @param $string
     * @param $params
     *
     * @return array|mixed|string|string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function addPlaceholdersToMessage($string, $params)
    {
        foreach ($params as $key => $value) {
            if (isset($value[0]) && $value[0] === '{') {
                $tempValueArray = json_decode($value, true);
                is_array($tempValueArray) && $value = $this->decodeDescription($value);
            }

            if ($key[0] === '!') {
                $key = substr($key, 1);
            } else {
                $value = $this->translationHelper->__($value);
            }

            $string = str_replace('%' . $key . '%', $value, $string);
        }

        return $string;
    }

    /**
     * @param $string
     * @param $links
     *
     * @return array|string|string[]
     */
    private function addLinksToMessage($string, $links)
    {
        $readMoreLinks = [];
        $resultString = $string;

        foreach ($links as $link) {
            preg_match('/!\w*_start!/', $resultString, $foundedStartMatches);

            if (empty($foundedStartMatches)) {
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

        if (!empty($readMoreLinks)) {
            $translation = $this->translationHelper;

            foreach ($readMoreLinks as &$link) {
                $link = '<a href="' . $link . '" target="_blank">' . $translation->__('here') . '</a>';
            }

            $delimiter = $translation->__('or');
            $readMoreString = $translation->__('Details') . ' ' . implode(' ' . $delimiter . ' ', $readMoreLinks) . '.';

            $resultString .= ' ' . $readMoreString;
        }

        return $resultString;
    }

    /**
     * @param $class
     * @param $type
     *
     * @return \Magento\Framework\Phrase|mixed|string
     */
    public function getActionTitleByClass($class, $type)
    {
        $reflectionClass = new \ReflectionClass($class);
        $tempConstants = $reflectionClass->getConstants();

        foreach ($tempConstants as $key => $value) {
            if ($key == '_' . $type) {
                return $this->translationHelper->__($key);
            }
        }

        return '';
    }

    /**
     * @param $class
     *
     * @return array
     */
    public function getActionsTitlesByClass($class)
    {
        switch ($class) {
            case \Ess\M2ePro\Model\Listing\Log::class:
            case \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::class:
                $prefix = 'ACTION_';
                break;

            case \Ess\M2ePro\Model\Synchronization\Log::class:
                $prefix = 'TASK_';
                break;
        }

        $reflectionClass = new \ReflectionClass($class);
        $tempConstants = $reflectionClass->getConstants();

        $actionsNames = [];
        foreach ($tempConstants as $key => $value) {
            if (substr($key, 0, strlen($prefix)) == $prefix) {
                $actionsNames[$key] = $value;
            }
        }

        $actionsValues = [];
        foreach ($actionsNames as $action => $valueAction) {
            foreach ($tempConstants as $key => $valueConstant) {
                if ($key === '_' . $action) {
                    $actionsValues[$valueAction] = $this->translationHelper->__($valueConstant);
                }
            }
        }

        return $actionsValues;
    }

    /**
     * @param $resultType
     *
     * @return mixed
     */
    public function getStatusByResultType($resultType)
    {
        $typesStatusesMap = [
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO  => \Ess\M2ePro\Helper\Data::STATUS_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS => \Ess\M2ePro\Helper\Data::STATUS_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING => \Ess\M2ePro\Helper\Data::STATUS_WARNING,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR   => \Ess\M2ePro\Helper\Data::STATUS_ERROR,
        ];

        return $typesStatusesMap[$resultType];
    }

    /**
     * @return string
     */
    public function platformInfo(): string
    {
        $platformInfo = [];
        $platformInfo['edition'] = $this->objectManager->get(\Ess\M2ePro\Helper\Magento::class)->getEditionName();
        $platformInfo['version'] = $this->objectManager->get(\Ess\M2ePro\Helper\Magento::class)->getVersion();

        return <<<DATA
-------------------------------- PLATFORM INFO -----------------------------------
Edition: {$platformInfo['edition']}
Version: {$platformInfo['version']}

DATA;
    }

    /**
     * @return string
     */
    public function moduleInfo(): string
    {
        $moduleInfo = [];
        $moduleInfo['name'] = $this->objectManager->get(\Ess\M2ePro\Helper\Module::class)->getName();
        $moduleInfo['version'] = $this->objectManager->get(\Ess\M2ePro\Helper\Module::class)->getPublicVersion();

        return <<<DATA
-------------------------------- MODULE INFO -------------------------------------
Name: {$moduleInfo['name']}
Version: {$moduleInfo['version']}

DATA;
    }
}
