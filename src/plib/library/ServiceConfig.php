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

        if (empty($this->display_name) || !is_string($this->display_name)) {
            throw new pm_Exception("{$this->unique_id}: Invalid display name");
        }

        if (empty($this->run_as_user) || !is_string($this->run_as_user)) {
            throw new pm_Exception("{$this->unique_id}: Invalid user");
        }

        if (empty($this->working_directory) || !is_string($this->working_directory)) {
            throw new pm_Exception("{$this->unique_id}: Invalid working directory");
        }

        if ($this->config_type === self::TYPE_PROCESS) {
            if (empty($this->process_command) || !is_string($this->process_command)) {
                throw new pm_Exception("{$this->unique_id}: Invalid command");
            }
        } else if ($this->config_type === self::TYPE_MANUAL) {
            if (empty($this->manual_start_command) || !is_string($this->manual_start_command)) {
                throw new pm_Exception("{$this->unique_id}: Invalid start command");
            }
            if (empty($this->manual_stop_command) || !is_string($this->manual_stop_command)) {
                throw new pm_Exception("{$this->unique_id}: Invalid stop command");
            }
            if (!empty($this->manual_restart_command) && !is_string($this->manual_restart_command)) {
                throw new pm_Exception("{$this->unique_id}: Invalid restart command");
            }
            if (empty($this->manual_status_command) || !is_string($this->manual_status_command)) {
                throw new pm_Exception("{$this->unique_id}: Invalid status command");
            }
        } else {
            throw new pm_Exception("{$this->unique_id}: Invalid configuration type");
        }
    }
}
