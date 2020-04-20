<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Requirements;

use Magento\Framework\Component\ComponentRegistrar;

/**
 * Class \Ess\M2ePro\Model\Requirements\Reader
 */
class Reader extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\Component\ComponentRegistrar */
    private $componentRegistrar;

    /** @var array|null */
    private $cachedData = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReadFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->componentRegistrar = $componentRegistrar;

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, \Ess\M2ePro\Helper\Module::IDENTIFIER);
        $moduleDir = $directoryReadFactory->create($path);

        $this->cachedData = $this->getHelper('Data')->jsonDecode($moduleDir->readFile('requirements.json'));
        $composerData = $this->getHelper('Data')->jsonDecode($moduleDir->readFile('composer.json'));

        $this->cachedData['composer'] = $composerData['require'];
    }

    //########################################

    public function getMemoryLimitData($dataPart = null)
    {
        $path = array_filter(['memory_limit', $dataPart]);
        return $this->getPath($path);
    }

    public function getExecutionTimeData($dataPart = null)
    {
        $path = array_filter(['execution_time', $dataPart]);
        return $this->getPath($path);
    }

    public function getMagentoVersionData($dataPart = null)
    {
        $path = array_filter(['magento_version', $dataPart]);
        return $this->getPath($path);
    }

    public function gePhpVersionData()
    {
        return $this->getPath(['composer', 'php']);
    }

    // ---------------------------------------

    protected function getPath(array $path, $data = null)
    {
        $data === null && $data = $this->cachedData;
        $pathPart = array_shift($path);

        if (isset($data[$pathPart])) {
            return !empty($path) ? $this->getPath($path, $data[$pathPart]) : $data[$pathPart];
        }

        return null;
    }

    //########################################
}
