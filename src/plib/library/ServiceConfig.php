<?php

class Modules_CustomServices_ServiceConfig
{
    const TYPE_PROCESS = 'process';
    const TYPE_MANUAL  = 'manual';

    // The unique identifier of this service.
    public $unique_id;
    // Arbitrary name for this service.
    public $display_name;

    // Which system user to run service commands as.
    public $run_as_user;
    // The working directory for all commands.
    public $working_directory;

    // The type of this configuration, either TYPE_PROCESS or TYPE_MANUAL.
    public $config_type;

    // Which command to execute to act as the service process.
    public $process_command;
    // What file to append stdout to.
    public $process_stdout_redirect_path;
    // What file to append stderr to.
    public $process_stderr_redirect_path;
    // What signal to use to stop the process.
    public $process_stop_signal;

    // The command that is used to start the service.
    public $manual_start_command;
    // The command that is used to stop the service.
    public $manual_stop_command;
    // The command that is used to restart the service (optional).
    public $manual_restart_command;
    // The command that is used to check the service status.
    public $manual_status_command;

    public function validate() {
        if (empty($this->unique_id) || !is_string($this->unique_id) ||
                trim($this->unique_id) !== $this->unique_id) {
            throw new pm_Exception("Invalid unique identifier '{$this->unique_id}'");
        }

        self::_requireNonEmptyString($this->display_name, 'Invalid display name');
        self::_requireNonEmptyString($this->run_as_user, 'Invalid user');
        self::_requireNonEmptyString($this->working_directory, 'Invalid working directory');

        if ($this->config_type === self::TYPE_PROCESS) {
            self::_requireNonEmptyString($this->process_command, 'Invalid command');
            self::_requireEmptyOrString($this->process_stdout_redirect_path, 'Invalid stdout redirect path');
            self::_requireEmptyOrString($this->process_stderr_redirect_path, 'Invalid stderr redirect path');
            self::_requireOption($this->process_stop_signal, ['SIGTERM', 'SIGINT', 'SIGKILL'], 'Invalid stop signal');
        } else if ($this->config_type === self::TYPE_MANUAL) {
            self::_requireNonEmptyString($this->manual_start_command, 'Invalid start command');
            self::_requireNonEmptyString($this->manual_stop_command, 'Invalid stop command');
            self::_requireEmptyOrString($this->manual_restart_command, 'Invalid restart command');
            self::_requireNonEmptyString($this->manual_status_command, 'Invalid status command');
        } else {
            throw new pm_Exception("{$this->unique_id}: Invalid configuration type");
        }
    }

    private function _requireNonEmpty($value, $desc) {
        if (empty($value)) {
            throw new pm_Exception("$desc: empty.");
        }
    }

    private function _requireString($value, $desc) {
        if (!is_string($value)) {
            throw new pm_Exception("$desc: not a string");
        }
    }

    private function _requireNonEmptyString($value, $desc) {
        self::_requireString($value, $desc);
        self::_requireNonEmpty($value, $desc);
    }

    private function _requireEmptyOrString($value, $desc) {
        if (!empty($value)) {
            self::_requireString($value, $desc);
        }
    }

    private function _requireOption($value, $options, $desc) {
        if (!in_array($value, $options, TRUE)) {
            $str = implode(', ', $options);
            throw new pm_Exception("$desc: must be one of $str");
        }
    }
}
