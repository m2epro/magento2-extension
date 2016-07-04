<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Development;

class Controller extends \Ess\M2ePro\Helper\AbstractHelper
{
    const REAL_MODULE = 'Ess_M2ePro';

    //########################################

    public function loadControllerAndGetClassName($controller)
    {
        $controllerFileName = $this->getControllerFileName($controller);
        if (!$this->validateControllerFileName($controllerFileName)) {
            return false;
        }

        $controllerClassName = $this->getControllerClassName($controller);
        if (!$controllerClassName) {
            return false;
        }

        // include controller file if needed
        if (!$this->_includeControllerClass($controllerFileName, $controllerClassName)) {
            return false;
        }

        return $controllerClassName;
    }

    //########################################

    public function getControllerFileName($controller)
    {
        $parts = explode('_', self::REAL_MODULE);
        $realModule = implode('_', array_splice($parts, 0, 2));
        $file = Mage::getModuleDir('controllers', $realModule); //todo correct Dir
        if (count($parts)) {
            $file .= DS . implode(DS, $parts);
        }
        $file .= DS.uc_words($controller, DS).'Controller.php';
        return $file;
    }

    public function validateControllerFileName($fileName)
    {
        return $fileName && is_readable($fileName) && false===strpos($fileName, '//');
    }

    // ---------------------------------------

    public function getControllerClassName($controller)
    {
        return self::REAL_MODULE.'_'.uc_words($controller).'Controller';
    }

    // ---------------------------------------

    protected function _includeControllerClass($controllerFileName, $controllerClassName)
    {
        if (!class_exists($controllerClassName, false)) {

            if (!file_exists($controllerFileName)) {
                return false;
            }

            include $controllerFileName;

            if (!class_exists($controllerClassName, false)) {
                //todo correct Exception
//                throw Mage::exception(
//                    'Mage_Core', Mage::helper('core')->__('Controller file was loaded but class does not exist')
//                );
            }
        }

        return true;
    }

    //########################################
}