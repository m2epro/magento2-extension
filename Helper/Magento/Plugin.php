<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

use Magento\Framework\App\Area;

class Plugin
{
    /** @var \Magento\Framework\App\Config\FileResolver  */
    private $fileResolver;
    /** @var \Magento\Framework\App\AreaList  */
    private $areaList;

    public function __construct(
        \Magento\Framework\App\Config\FileResolver $fileResolver,
        \Magento\Framework\App\AreaList $areaList
    ) {
        $this->fileResolver = $fileResolver;
        $this->areaList = $areaList;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getAll(): array
    {
        $plugins = [];

        foreach ($this->scanConfigFiles('di.xml') as $fileName) {
            $plugins = array_merge_recursive($plugins, $this->scanConfigFile($fileName));
        }

        return $plugins;
    }

    // ----------------------------------------

    /**
     * @param $name
     *
     * @return array
     */
    private function scanConfigFiles($name): array
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

    /**
     * @param $fileName
     *
     * @return array
     */
    private function scanConfigFile($fileName): array
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

    /**
     * @param $plugin
     *
     * @return bool
     */
    private function isValidPlugin($plugin): bool
    {
        return ($plugin instanceof \DOMElement) && $plugin->tagName === 'plugin' && $plugin->getAttribute('type');
    }

    /**
     * @param $class
     *
     * @return array
     */
    private function getMethods($class): array
    {
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            return [];
        }

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
