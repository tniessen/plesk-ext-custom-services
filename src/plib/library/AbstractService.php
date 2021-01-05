<?php

abstract class Modules_CustomServices_AbstractService extends pm_SystemService_Service
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getId()
    {
        return $this->config->unique_id;
    }

    public function getName()
    {
        if (pm_Settings::get('service_name_add_prefix', '1') !== '0') {
            return "Custom service: {$this->config->display_name}";
        } else {
            return $this->config->display_name;
        }
    }
}
