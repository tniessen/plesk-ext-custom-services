<?php

class IndexController extends pm_Controller_Action
{
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
                'type' => $config->config_type
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
        $this->view->pageTitle = 'Custom Services';
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
        $form->addElement('text', 'display_name', [
            'label' => 'Name',
            'value' => $config->display_name,
            'readonly' => TRUE,
            'description' => 'Display name of this service.'
        ]);
        $form->addElement('text', 'config_type', [
            'label' => 'Type',
            'value' => $config->config_type,
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
            'description' => "Do you want to delete the service \"{$config->display_name}\" ({$config->unique_id})? This action cannot be undone."
        ]);
        $form->addElement('checkbox', 'confirm_delete', [
            'label' => "Yes",
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

        if ($t_process) {
            $this->view->pageTitle = 'Add simple custom service';
        } else {
            $this->view->pageTitle = 'Add manual custom service';
        }

        $form = new pm_Form_Simple();

        if ($t_process) {
            $form->addElement('description', 'description', [
                'description' => 'Add a custom service to Plesk that wraps a process.'
            ]);
        } else {
            $form->addElement('description', 'description', [
                'description' => 'Add a custom service to Plesk that is controlled by custom commands.'
            ]);
        }

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
                $config->process_command = $form->getValue('process_command');
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

    public function addManualServiceAction()
    {
        $this->view->pageTitle = 'Add manual custom service';
        $form = new pm_Form_Simple();
        $form->addControlButtons();
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
        $form->addElement('text', 'unique_id', [
            'label' => 'Unique identifier',
            'value' => $config->unique_id,
            'readonly' => TRUE,
            'description' => 'This string uniquely identifies the service.'
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
            'cancelLink' => pm_Context::getBaseUrl()
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            foreach (['display_name', 'run_as_user', 'working_directory'] as $prop) {
                $config->{$prop} = $form->getValue($prop);
            }

            if ($config->config_type === Modules_CustomServices_ServiceConfig::TYPE_PROCESS) {
                $config->process_command = $form->getValue('process_command');
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
}
