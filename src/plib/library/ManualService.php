<?php

class Modules_CustomServices_ManualService extends Modules_CustomServices_AbstractService
{
    private function _interact($command)
    {
        $args = [
            $this->config->run_as_user,
            $this->config->working_directory,
            $command
        ];
        return pm_ApiCli::callSbin('service-interact', $args, pm_ApiCli::RESULT_FULL);
    }

    public function onStart()
    {
        $result = $this->_interact($this->config->manual_start_command);
        if ($result['code'] !== 0) {
            throw new pm_Exception("Failed to start '{$this->getName()}': {$result['stderr']}");
        }
    }

    public function onStop()
    {
        $result = $this->_interact($this->config->manual_stop_command);
        if ($result['code'] !== 0) {
            throw new pm_Exception("Failed to stop '{$this->getName()}': {$result['stderr']}");
        }
    }

    public function onRestart()
    {
        if (empty($this->config->manual_restart_command)) {
            $this->onStop();
            $this->onStart();
        } else {
            $result = $this->_interact($this->config->manual_restart_command);
            if ($result['code'] !== 0) {
                throw new pm_Exception("Failed to restart '{$this->getName()}': {$result['stderr']}");
            }
        }
    }

    public function isRunning()
    {
        $result = $this->_interact($this->config->manual_status_command);
        return $result['code'] === 0;
    }
}

