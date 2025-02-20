<?php

class IndexController extends pm_Controller_Action
{
    private static function _configTypeName($type) {
        return [
            Modules_CustomServices_ServiceConfig::TYPE_PROCESS => 'Process',
            Modules_CustomServices_ServiceConfig::TYPE_MANUAL => 'Manual'
        ][$type];
    }

    private static function _configTypeDescription($type) {
        return [
            Modules_CustomServices_ServiceConfig::TYPE_PROCESS => 'This service is managed by this extension. When the service is started, a new process group is spawned by executing the given command. The process group leader must remain active until stopped.',
            Modules_CustomServices_ServiceConfig::TYPE_MANUAL => 'This service can run independently of this extension. Plesk uses the below commands to interact with the service.'
        ][$type];
    }

    public function init()
    {
        parent::init();
        $this->view->pageTitle = 'Custom Services';
        $this->view->tabs = [
            [
                'title' => 'Services',
                'action' => 'list'
            ],
            [
                'title' => 'Settings',
                'action' => 'settings'
            ]
        ];
    }

    public function indexAction()
    {
        $this->_forward('list');
    }

    private function _getServiceList()
    {
        // Load the list of services.
        Modules_CustomServices_DataLayer::init();
        $configs = Modules_CustomServices_DataLayer::loadServiceConfigurations();

        // Configs are objects, but the list view needs associative arrays.
        $config_to_array = function($config) {
            return [
                'name' => '<a href="' . htmlspecialchars(pm_Context::getActionUrl('index', 'view') . '/id/' . urlencode($config->unique_id)) . '">' . htmlspecialchars($config->display_name) . '</a>',
                'type' => self::_configTypeName($config->config_type),
                'plesk_service_id' => '<code>' . htmlspecialchars('ext-' . pm_Context::getModuleId() . '-' . $config->unique_id) . '</code>',
                'run_as_user' => $config->run_as_user
            ];
        };
        $data = array_map($config_to_array, $configs);

        // Prepare the list for presentation.
        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns([
            'name' => [
                'title' => 'Name',
                'noEscape' => TRUE,
                'searchable' => TRUE,
                'sortable' => TRUE
            ],
            'type' => [
                'title' => 'Type',
                'noEscape' => FALSE,
                'searchable' => FALSE,
                'sortable' => FALSE
            ],
            'plesk_service_id' => [
                'title' => 'Service ID',
                'noEscape' => TRUE,
                'searchable' => TRUE,
                'sortable' => FALSE
            ],
            'run_as_user' => [
                'title' => 'System user',
                'noEscape' => FALSE,
                'searchable' => TRUE,
                'sortable' => TRUE
            ]
        ]);
        $list->setDataUrl(['action' => 'list-data']);
        $list->setTools([
            [
                'title' => 'Add service (simple)',
                'description' => 'Add a new service that wraps a process',
                'class' => 'sb-add-new',
                'link' => pm_Context::getActionUrl('index', 'add') . '/type/process'
            ],
            [
                'title' => 'Add service (manual)',
                'description' => 'Add a new manually controlled service',
                'class' => 'sb-add-new',
                'link' => pm_Context::getActionUrl('index', 'add') . '/type/manual'
            ]
        ]);
        return $list;
    }

    public function listAction()
    {
        $list = $this->_getServiceList();
        $this->view->list = $list;
    }

    public function listDataAction()
    {
        $list = $this->_getServiceList();
        $this->_helper->json($list->fetchData());
    }

    private function _loadSingleConfiguration($id)
    {
        Modules_CustomServices_DataLayer::init();
        $configs = Modules_CustomServices_DataLayer::loadServiceConfigurations();
        foreach($configs as $config) {
            if ($id === $config->unique_id) {
                return $config;
            }
        }
        return FALSE;
    }

    public function viewAction()
    {
        $config = $this->_loadSingleConfiguration($this->getRequest()->getParam('id'));
        if ($config === FALSE) {
            $this->_forward('list');
            return;
        }

        $this->view->pageTitle = 'Custom Service: ' . $config->display_name;

        $form = new pm_Form_Simple();
        $form->addElement('text', 'unique_id', [
            'label' => 'Unique identifier',
            'value' => $config->unique_id,
            'readonly' => TRUE,
            'description' => 'This string uniquely identifies the service.'
        ]);
        $form->addElement('text', 'plesk_service_id', [
            'label' => 'Service ID',
            'value' => 'ext-' . pm_Context::getModuleId() . '-' . $config->unique_id,
            'readonly' => TRUE,
            'description' => 'Use this identifier to interact with the service through Plesk.'
        ]);
        $form->addElement('text', 'display_name', [
            'label' => 'Name',
            'value' => $config->display_name,
            'readonly' => TRUE,
            'description' => 'Display name of this service.'
        ]);
        $form->addElement('text', 'config_type', [
            'label' => 'Type',
            'value' => self::_configTypeName($config->config_type),
            'description' => self::_configTypeDescription($config->config_type),
            'readonly' => TRUE
        ]);
        $form->addElement('text', 'run_as_user', [
            'label' => 'System user',
            'value' => $config->run_as_user,
            'readonly' => TRUE,
            'description' => 'The process will run under this user account.'
        ]);
        $form->addElement('text', 'working_directory', [
            'label' => 'Working directory',
            'value' => $config->working_directory,
            'readonly' => TRUE,
            'description' => 'The process will be started from this directory.'
        ]);

        if ($config->config_type === Modules_CustomServices_ServiceConfig::TYPE_PROCESS) {
            $form->addElement('text', 'process_command', [
                'label' => 'Command',
                'value' => $config->process_command,
                'readonly' => TRUE,
                'description' => 'This command will be used to start the service, and the process must remain active while the service is running.'
            ]);

            $form->addElement('text', 'process_stdout_redirect_path', [
                'label' => 'Redirect output',
                'value' => $config->process_stdout_redirect_path,
                'readonly' => TRUE,
                'description' => empty($config->process_stdout_redirect_path) ? 'The standard output stream of the process will be discarded.' : 'The standard output stream of the process will be appended to this file.'
            ]);
            $form->addElement('text', 'process_stderr_redirect_path', [
                'label' => 'Redirect errors',
                'value' => $config->process_stderr_redirect_path,
                'readonly' => TRUE,
                'description' => empty($config->process_stderr_redirect_path) ? 'The standard error stream of the process will be merged with the standard output stream.' : 'The standard error stream of the process will be appended to this file.'
            ]);

            $form->addElement('text', 'process_stop_signal', [
                'label' => 'Stop signal',
                'value' => $config->process_stop_signal,
                'readonly' => TRUE,
                'description' => 'This signal will be used to stop the service process.'
            ]);
        } else {
            $form->addElement('text', 'manual_start_command', [
                'label' => 'Start command',
                'value' => $config->manual_start_command,
                'readonly' => TRUE,
                'description' => 'This command will be used to start the service.'
            ]);
            $form->addElement('text', 'manual_stop_command', [
                'label' => 'Stop command',
                'value' => $config->manual_stop_command,
                'readonly' => TRUE,
                'description' => 'This command will be used to stop the service.'
            ]);
            $form->addElement('text', 'manual_restart_command', [
                'label' => 'Restart command',
                'value' => $config->manual_restart_command,
                'readonly' => TRUE,
                'description' => 'This command will be used to restart the service. If not specified, the stop and start commands will be used to restart the service.'
            ]);
            $form->addElement('text', 'manual_status_command', [
                'label' => 'Status command',
                'value' => $config->manual_status_command,
                'readonly' => TRUE,
                'description' => 'This command will be used to query the status of the service.'
            ]);
        }

        $this->view->form = $form;

        $this->view->smallTools = [
            [
                'title' => 'Edit',
                'description' => 'Edit this service',
                'class' => 'sb-change-subscription',
                'controller' => 'index',
                'link' => pm_Context::getActionUrl('index', 'edit') . '/id/' . urlencode($config->unique_id)
            ],
            [
                'title' => 'Delete',
                'description' => 'Delete this service',
                'class' => 'sb-remove-selected',
                'link' => pm_Context::getActionUrl('index', 'delete') . '/id/' . urlencode($config->unique_id)
            ],
            [
                'title' => 'Back',
                'description' => 'Go back to the list',
                'class' => 'sb-button1',
                'controller' => 'index',
                'action' => 'list'
            ]
        ];
    }

    public function deleteAction()
    {
        $config = $this->_loadSingleConfiguration($this->getRequest()->getParam('id'));
        if ($config === FALSE) {
            $this->_forward('list');
            return;
        }

        $this->view->pageTitle = 'Delete custom service: ' . $config->display_name;

        $form = new pm_Form_Simple();
        $form->addElement('description', 'description', [
            'description' => 'Do you want to delete this service? This action cannot be undone.'
        ]);
        $form->addElement('text', 'unique_id', [
            'label' => 'Unique identifier',
            'value' => $config->unique_id,
            'readonly' => TRUE,
            'description' => 'This string uniquely identifies the service.'
        ]);
        $form->addElement('text', 'plesk_service_id', [
            'label' => 'Service ID',
            'value' => 'ext-' . pm_Context::getModuleId() . '-' . $config->unique_id,
            'readonly' => TRUE,
            'description' => 'Use this identifier to interact with the service through Plesk.'
        ]);
        $form->addElement('text', 'display_name', [
            'label' => 'Name',
            'value' => $config->display_name,
            'readonly' => TRUE,
            'description' => 'Display name of this service.'
        ]);
        $form->addElement('checkbox', 'confirm_delete', [
            'label' => 'Yes, delete this service.',
            'required' => TRUE
        ]);
        $form->addControlButtons([
            'sendTitle' => 'Delete service',
            'cancelLink' => pm_Context::getActionUrl('index', 'view') . '/id/' . urlencode($config->unique_id)
        ]);
        $this->view->form = $form;

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            if ($form->getValue('confirm_delete') !== '1') {
                $this->_status->addError('Deletion aborted due to failed confirmation.');
            } else {
                Modules_CustomServices_DataLayer::deleteServiceConfiguration($config->unique_id);
                $this->_status->addInfo("Custom service '{$config->display_name}' was deleted.");
            }
            $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
        }

        $this->view->form = $form;
    }

    public function addAction()
    {
        $config_type = $this->getRequest()->getParam('type');
        $t_process = $config_type === Modules_CustomServices_ServiceConfig::TYPE_PROCESS;
        $t_manual  = $config_type === Modules_CustomServices_ServiceConfig::TYPE_MANUAL;

        if ($t_process !== !$t_manual) {
            $this->_forward('list');
            return;
        }

        $this->view->pageTitle = 'Add custom service: ' . self::_configTypeName($config_type);

        $form = new pm_Form_Simple();
        $form->addElement('description', 'description', [
            'description' => self::_configTypeDescription($config_type)
        ]);

        $form->addElement('text', 'unique_id', [
            'label' => 'Unique identifier',
             'value' => bin2hex(random_bytes(8)),
             'required' => TRUE,
             'description' => 'This string uniquely identifies the service.'
        ]);
        $form->addElement('text', 'display_name', [
            'label' => 'Name',
            'value' => '',
            'required' => TRUE,
            'description' => 'Display name of this service.'
        ]);
        $form->addElement('text', 'run_as_user', [
            'label' => 'System user',
            'value' => '',
            'required' => TRUE,
            'description' => 'The process will run under this user account.'
        ]);
        $form->addElement('text', 'working_directory', [
            'label' => 'Working directory',
            'value' => '/',
            'required' => TRUE,
            'description' => 'The process will be started from this directory.'
        ]);

        if ($t_process) {
            $form->addElement('text', 'process_command', [
                'label' => 'Command',
                'value' => '',
                'required' => TRUE,
                'description' => 'This command will be used to start the service, and the process must remain active while the service is running.'
            ]);

            $form->addElement('text', 'process_stdout_redirect_path', [
                'label' => 'Redirect output',
                'value' => '',
                'required' => FALSE,
                'description' => 'If this field is empty, the standard output stream of the process will be discarded. Otherwise, data written to the standard output stream will be appended to this file.'
            ]);
            $form->addElement('text', 'process_stderr_redirect_path', [
                'label' => 'Redirect errors',
                'value' => '',
                'required' => FALSE,
                'description' => 'If this field is empty, the standard error stream of the process will be merged with the standard output stream. Otherwise, data written to the standard error stream will be appended to this file.'
            ]);

            $form->addElement('radio', 'process_stop_signal', [
                'label' => 'Stop signal',
                'value' => 'SIGTERM',
                'multiOptions' => [
                        'SIGTERM' => 'SIGTERM',
                        'SIGINT'  => 'SIGINT',
                        'SIGKILL' => 'SIGKILL'
                ],
                'required' => TRUE,
                'description' => 'This signal will be used to stop the service process.'
            ]);
        } else {
            $form->addElement('text', 'manual_start_command', [
                'label' => 'Start command',
                'value' => '',
                'required' => TRUE,
                'description' => 'This command will be used to start the service.'
            ]);
            $form->addElement('text', 'manual_stop_command', [
                'label' => 'Stop command',
                'value' => '',
                'required' => TRUE,
                'description' => 'This command will be used to stop the service.'
            ]);
            $form->addElement('text', 'manual_restart_command', [
                'label' => 'Restart command',
                'value' => '',
                'required' => FALSE,
                'description' => 'This command will be used to restart the service. If not specified, the stop and start commands will be used to restart the service.'
            ]);
            $form->addElement('text', 'manual_status_command', [
                'label' => 'Status command',
                'value' => '',
                'required' => TRUE,
                'description' => 'This command will be used to query the status of the service.'
            ]);
        }

        $form->addControlButtons([
            'cancelLink' => pm_Context::getBaseUrl()
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $config = new Modules_CustomServices_ServiceConfig();
            $config->config_type = $config_type;
            foreach (['unique_id', 'display_name', 'run_as_user', 'working_directory'] as $prop) {
                $config->{$prop} = $form->getValue($prop);
            }
            if ($t_process) {
                foreach(['process_command', 'process_stdout_redirect_path', 'process_stderr_redirect_path', 'process_stop_signal'] as $prop) {
                    $config->{$prop} = $form->getValue($prop);
                }
            } else {
                foreach(['manual_start_command', 'manual_stop_command', 'manual_restart_command', 'manual_status_command'] as $prop) {
                    $config->{$prop} = $form->getValue($prop);
                }
            }
            Modules_CustomServices_DataLayer::init();
            Modules_CustomServices_DataLayer::addServiceConfiguration($config);
            $this->_status->addInfo("Custom service '{$config->display_name}' was added.");
            $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
        }

        $this->view->form = $form;
    }

    public function editAction()
    {
        $config = $this->_loadSingleConfiguration($this->getRequest()->getParam('id'));
        if ($config === FALSE) {
            $this->_forward('list');
            return;
        }

        $this->view->pageTitle = 'Edit custom service';

        $form = new pm_Form_Simple();
        $form->addElement('description', 'description', [
            'description' => self::_configTypeDescription($config->config_type)
        ]);
        $form->addElement('text', 'unique_id', [
            'label' => 'Unique identifier',
            'value' => $config->unique_id,
            'readonly' => TRUE,
            'description' => 'This string uniquely identifies the service.'
        ]);
        $form->addElement('text', 'plesk_service_id', [
            'label' => 'Service ID',
            'value' => 'ext-' . pm_Context::getModuleId() . '-' . $config->unique_id,
            'readonly' => TRUE,
            'description' => 'Use this identifier to interact with the service through Plesk.'
        ]);
        $form->addElement('text', 'display_name', [
            'label' => 'Name',
            'value' => $config->display_name,
            'required' => TRUE,
            'description' => 'Display name of this service.'
        ]);
        $form->addElement('text', 'run_as_user', [
            'label' => 'System user',
            'value' => $config->run_as_user,
            'required' => TRUE,
            'description' => 'The process will run under this user account.'
        ]);
        $form->addElement('text', 'working_directory', [
            'label' => 'Working directory',
            'value' => $config->working_directory,
            'required' => TRUE,
            'description' => 'The process will be started from this directory.'
        ]);

        if ($config->config_type === Modules_CustomServices_ServiceConfig::TYPE_PROCESS) {
            $form->addElement('text', 'process_command', [
                'label' => 'Command',
                'value' => $config->process_command,
                'required' => TRUE,
                'description' => 'This command will be used to start the service, and the process must remain active while the service is running.'
            ]);

            $form->addElement('text', 'process_stdout_redirect_path', [
                'label' => 'Redirect output',
                'value' => $config->process_stdout_redirect_path,
                'required' => FALSE,
                'description' => 'If this field is empty, the standard output stream of the process will be discarded. Otherwise, data written to the standard output stream will be appended to this file.'
            ]);
            $form->addElement('text', 'process_stderr_redirect_path', [
                'label' => 'Redirect errors',
                'value' => $config->process_stderr_redirect_path,
                'required' => FALSE,
                'description' => 'If this field is empty, the standard error stream of the process will be merged with the standard output stream. Otherwise, data written to the standard error stream will be appended to this file.'
            ]);

            $form->addElement('radio', 'process_stop_signal', [
                'label' => 'Stop signal',
                'value' => $config->process_stop_signal,
                'multiOptions' => [
                        'SIGTERM' => 'SIGTERM',
                        'SIGINT'  => 'SIGINT',
                        'SIGKILL' => 'SIGKILL'
                ],
                'required' => TRUE,
                'description' => 'This signal will be used to stop the service process.'
            ]);
        } else {
            $form->addElement('text', 'manual_start_command', [
                'label' => 'Start command',
                'value' => $config->manual_start_command,
                'required' => TRUE,
                'description' => 'This command will be used to start the service.'
            ]);
            $form->addElement('text', 'manual_stop_command', [
                'label' => 'Stop command',
                'value' => $config->manual_stop_command,
                'required' => TRUE,
                'description' => 'This command will be used to stop the service.'
            ]);
            $form->addElement('text', 'manual_restart_command', [
                'label' => 'Restart command',
                'value' => $config->manual_restart_command,
                'required' => FALSE,
                'description' => 'This command will be used to restart the service. If not specified, the stop and start commands will be used to restart the service.'
            ]);
            $form->addElement('text', 'manual_status_command', [
                'label' => 'Status command',
                'value' => $config->manual_status_command,
                'required' => TRUE,
                'description' => 'This command will be used to query the status of the service.'
            ]);
        }

        $form->addControlButtons([
            'cancelLink' => pm_Context::getActionUrl('index', 'view') . '/id/' . urlencode($config->unique_id)
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            foreach (['display_name', 'run_as_user', 'working_directory'] as $prop) {
                $config->{$prop} = $form->getValue($prop);
            }

            if ($config->config_type === Modules_CustomServices_ServiceConfig::TYPE_PROCESS) {
                foreach(['process_command', 'process_stdout_redirect_path', 'process_stderr_redirect_path', 'process_stop_signal'] as $prop) {
                    $config->{$prop} = $form->getValue($prop);
                }
            } else {
                foreach(['manual_start_command', 'manual_stop_command', 'manual_restart_command', 'manual_status_command'] as $prop) {
                    $config->{$prop} = $form->getValue($prop);
                }
            }

            Modules_CustomServices_DataLayer::updateServiceConfiguration($config);
            $this->_status->addInfo("The changes to the service '{$config->display_name}' were saved.");
            $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
        }

        $this->view->form = $form;
    }

    public function settingsAction()
    {
        $form = new pm_Form_Simple();
        $form->addElement('checkbox', 'service_name_add_prefix', [
            'label' => 'Add a prefix to custom service names',
            'value' => pm_Settings::get('service_name_add_prefix', '1'),
            'required' => TRUE
        ]);

        $form->addControlButtons([
            'cancelHidden' => TRUE
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            pm_Settings::set('service_name_add_prefix', $form->getValue('service_name_add_prefix'));
            $this->_status->addInfo('Settings saved.');
            $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
        }

        $this->view->form = $form;
    }
}
