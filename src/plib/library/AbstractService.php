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
        return "Custom service: {$this->config->display_name}";
    }
}
