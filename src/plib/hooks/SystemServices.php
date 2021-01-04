<?php

class Modules_CustomServices_SystemServices extends pm_Hook_SystemServices
{
    public function getServices()
    {
        Modules_CustomServices_DataLayer::init();
        $configs = Modules_CustomServices_DataLayer::loadServiceConfigurations();

        $wrap = function($config) {
            if ($config->config_type === Modules_CustomServices_ServiceConfig::TYPE_PROCESS) {
                return new Modules_CustomServices_ProcessService($config);
            } else {
                return new Modules_CustomServices_ManualService($config);
            }
        };

        return array_map($wrap, $configs);
    }
}
