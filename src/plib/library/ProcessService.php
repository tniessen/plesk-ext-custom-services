<?php

class Modules_CustomServices_ProcessService extends Modules_CustomServices_AbstractService
{
    private function _procservicectrl($action)
    {
        $rootdir = pm_ProductInfo::getProductRootDir();
        $cmd = "$rootdir/admin/sbin/modules/custom-services/procservicectrl $action";
        $args = [$this->config->run_as_user, $this->config->working_directory, $cmd];

        if (empty($this->config->process_stdout_redirect_path)) {
          $redirect_stdout = '/dev/null';
        } else {
          $redirect_stdout = $this->config->process_stdout_redirect_path;
        }

        if (empty($this->config->process_stderr_redirect_path)) {
          $redirect_stderr = '';
        } else {
          $redirect_stderr = $this->config->process_stderr_redirect_path;
        }

        $env = [
            'PLESK_CUSTOM_SERVICE_ID' => $this->getId(),
            'PLESK_CUSTOM_SERVICE_VAR_RUN_DIR' => "/var/run/plesk-custom-service-{$this->getId()}",
            'PLESK_CUSTOM_SERVICE_COMMAND' => $this->config->process_command,
            'PLESK_CUSTOM_SERVICE_REDIRECT_STDOUT' => $redirect_stdout,
            'PLESK_CUSTOM_SERVICE_REDIRECT_STDERR' => $redirect_stderr,
            'PLESK_CUSTOM_SERVICE_STOP_SIGNAL' => $this->config->process_stop_signal
        ];
        $result = pm_ApiCli::callSbin('service-interact', $args, pm_ApiCli::RESULT_FULL, $env);
        if ($result['code'] !== 0) {
            throw new pm_Exception("$action failed for '{$this->getName()}': {$result['stderr']}");
        }
        return $result;
    }

    public function onStart()
    {
        $this->_procservicectrl('start');
    }

    public function onStop()
    {
        $this->_procservicectrl('stop');
    }

    public function onRestart()
    {
        $this->onStop();
        $this->onStart();
    }

    public function isRunning()
    {
        $result = $this->_procservicectrl('status');
        return strpos($result['stdout'], 'Status: active') === 0;
    }
}

