<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

use \Magento\Framework\App\Area;

/**
 * Class \Ess\M2ePro\Helper\Magento\Plugin
 */
class Plugin extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $fileResolver;
    protected $areaList;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Config\FileResolver $fileResolver,
        \Magento\Framework\App\AreaList $areaList,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($helperFactory, $context);

        $this->fileResolver = $fileResolver;
        $this->areaList = $areaList;
    }

    //########################################

    public function getAll()
    {
        $plugins = [];

        foreach ($this->scanConfigFiles('di.xml') as $fileName) {
            $plugins = array_merge_recursive($plugins, $this->scanConfigFile($fileName));
        }

        return $plugins;
    }

    //########################################

    private function scanConfigFiles($name)
    {
        $files = [];
        $areaCodes = array_merge(
            ['primary', Area::AREA_GLOBAL],
            $this->areaList->getCodes()
        );

        foreach ($areaCodes as $area) {
            $files = array_merge_recursive(
                $files,
                $this->fileResolver->get($name, $area)->toArray()
            );
        }

        return !empty($files) ? array_keys($files) : [];
    }

    private function scanConfigFile($fileName)
    {
        $dom = new \DOMDocument();
        $dom->load($fileName);

        $xpath = new \DOMXPath($dom);
        $results = $xpath->query('//plugin/..');

        $plugins = [];

        foreach ($results as $result) {
            /** @var \DOMElement $result */

            $className = ltrim($result->getAttribute('name'), '\\');
            $disabled  = $result->getAttribute('disabled');

            foreach ($result->childNodes as $plugin) {
                /** @var \DOMElement $plugin */

                if (!$this->isValidPlugin($plugin)) {
                    continue;
                }

                $plugins[$className][] = [
                    'class'    => ltrim($plugin->getAttribute('type'), '\\'),
                    'disabled' => (isset($disabled) && $disabled === 'false'),
                    'methods'  => $this->getMethods($plugin->getAttribute('type'))
                ];
            }
        }

        return $plugins;
    }

    private function isValidPlugin($plugin)
    {
        return ($plugin instanceof \DOMElement) && $plugin->tagName === 'plugin' && $plugin->getAttribute('type');
    }

    private function getMethods($class)
    {
        $reflection = new \ReflectionClass($class);

        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            foreach (['before', 'after', 'around'] as $prefix) {
                if (strpos($method->name, $prefix) === 0) {
                    $methods[] = $method->name;
                    break;
                }
            }
        }

        return $methods;
    }

    //########################################
}
