<?php

class Modules_CustomServices_DataLayer
{
    private static $var_dir;

    public static function init() {
        pm_Context::init('custom-services');
        self::$var_dir = pm_Context::getVarDir();

        $services_path = self::servicesFilePath();
        if (!file_exists($services_path)) {
            file_put_contents($services_path, '[]');
        }
    }

    private static function servicesFilePath() {
        return self::$var_dir . '/services.json';
    }

    public static function loadServiceConfigurations() {
        $json = json_decode(file_get_contents(self::servicesFilePath()));
        $parse_config = function($entry) {
            $config = new Modules_CustomServices_ServiceConfig();
            foreach (array_keys(get_object_vars($config)) as $name) {
                $config->{$name} = $entry->{$name};
            }
            $config->validate();
            return $config;
        };
        return array_map($parse_config, $json);
    }

    private static function writeServiceConfigurations($all) {
        file_put_contents(self::servicesFilePath(), json_encode($all));
    }

    public static function addServiceConfiguration($config) {
        $config->validate();
        $all = self::loadServiceConfigurations();
        foreach ($all as $existing_config) {
            if (strcasecmp($config->unique_id, $existing_config->unique_id) === 0) {
                throw new pm_Exception('Identifier collision.');
            }
        }
        array_push($all, $config);
        self::writeServiceConfigurations($all);
    }

    public static function updateServiceConfiguration($config) {
        $config->validate();
        $all = self::loadServiceConfigurations();
        foreach ($all as $index => $existing_config) {
            if ($existing_config->unique_id === $config->unique_id) {
                $all[$index] = $config;
                self::writeServiceConfigurations($all);
                return;
            }
        }
        throw new pm_Exception('Unknown service.');
    }

    public static function deleteServiceConfiguration($id) {
        $all = self::loadServiceConfigurations();
        foreach ($all as $index => $config) {
            if ($config->unique_id === $id) {
                array_splice($all, $index, 1);
                self::writeServiceConfigurations($all);
                return;
            }
        }
        throw new pm_Exception('Unknown service.');
    }
}
