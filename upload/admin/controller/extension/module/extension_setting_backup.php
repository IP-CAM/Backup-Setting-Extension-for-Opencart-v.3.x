<?php
class ControllerExtensionModuleExtensionSettingBackup extends Controller {
    private $dir = 'esb';
    private $error = array();

    public function index() {

        $this->load->language('extension/module/extension_setting_backup');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_extension_setting_backup', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/extension_setting_backup', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/extension_setting_backup', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_extension_setting_backup_status'])) {
            $data['module_extension_setting_backup_status'] = $this->request->post['module_extension_setting_backup_status'];
        } else {
            $data['module_extension_setting_backup_status'] = $this->config->get('module_extension_setting_backup_status');
        }
        if (isset($this->request->post['module_extension_setting_backup_clean_interval'])) {
            $data['module_extension_setting_backup_clean_interval'] = $this->request->post['module_extension_setting_backup_clean_interval'];
        } else {
            $data['module_extension_setting_backup_clean_interval'] = (int)$this->config->get('module_extension_setting_backup_clean_interval');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['backups'] = array();
        foreach(glob(DIR_STORAGE . $this->dir . '/*') as $type){
            $type_info = pathinfo($type);
            $this->load->language('extension/extension/' . $type_info['filename'], 'extension');

            $data['backups'][$type_info['filename']]['name'] = $this->language->get('extension')->get('heading_title');

            foreach(glob($type .'/*') as $extension){
                $extension_info = pathinfo($extension);

                foreach(glob($extension . '/*') as $extensionData){
                    $extensionData_info = pathinfo($extensionData);

                    $this->load->language('extension/' . $type_info['filename'] . '/' . $extension_info['filename']);
                    $data['backups'][$type_info['filename']]['extensions'][$extension_info['filename']]['name'] = $this->language->get('heading_title');
                    if (is_dir($extensionData)) {

                        $this->load->model('setting/module');
                        $extensionInfo = $this->model_setting_module->getModule($extensionData_info['filename']);

                        $data['backups'][$type_info['filename']]['extensions'][$extension_info['filename']]['children'][$extensionData_info['filename']]['name'] = $extensionInfo['name'];

                        foreach(glob($extensionData . '/*') as $setting){
                            $setting_info = pathinfo($setting);

                            $data['backups'][$type_info['filename']]['extensions'][$extension_info['filename']]['children'][$extensionData_info['filename']]['backups'][] = array(
                                'name' => date('m/d/Y H:i:s', $setting_info['filename']),
                                'link' => $this->url->link('extension/module/extension_setting_backup/restore', 'type=' . $type_info['filename'] . '&extension=' . $extension_info['filename'] . '&extension_id=' . $extensionData_info['filename'] . '&backup=' . $setting_info['filename'] . '&user_token=' . $this->session->data['user_token'])
                            );
                        }
                    }else{
                        $data['backups'][$type_info['filename']]['extensions'][$extension_info['filename']]['backups'][] = array(
                            'name' => date('m/d/Y H:i:s', $extensionData_info['filename']),
                            'link' => $this->url->link('extension/module/extension_setting_backup/restore', 'type=' . $type_info['filename'] . '&extension=' . $extension_info['filename'] . '&backup=' . $extensionData_info['filename'] . '&user_token=' . $this->session->data['user_token'])
                        );
                    }
                }
            }
        }

        $this->response->setOutput($this->load->view('extension/module/extension_setting_backup', $data));
    }

    public function restore(){
        $this->load->language('extension/module/extension_setting_backup');
        $this->load->model('setting/setting');
        $this->load->model('setting/module');

        $type = $this->request->get['type'] ?? '';
        $extension = $this->request->get['extension'] ?? '';
        $extension_id = $this->request->get['extension_id'] ?? '';
        $backup = $this->request->get['backup'] ?? '';

        if($extension_id) {
            $file = file_get_contents(DIR_STORAGE . $this->dir . '/' . $type .'/' . $extension . '/' . $extension_id . '/' . $backup . '.json');

            $this->model_setting_module->editModule($extension_id, json_decode($file, true));
        } else {
            $file = file_get_contents(DIR_STORAGE . $this->dir . '/' . $type .'/' . $extension . '/' . $backup . '.json');

            $this->model_setting_setting->editSetting($type . '_' . $extension, json_decode($file, true));

        }

        $this->session->data['success'] = $this->language->get('text_success');

        $this->response->redirect($this->url->link('extension/module/extension_setting_backup', 'user_token=' . $this->session->data['user_token']));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/extension_setting_backup')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('user/user_group');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/extension_setting_backup');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/extension_setting_backup');
        if (!is_dir(DIR_STORAGE . $this->dir)) {
            mkdir(DIR_STORAGE . $this->dir, 0777, true);
        }
    }

    public function uninstall() {
        $this->load->model('user/user_group');
        if (is_dir(DIR_STORAGE . $this->dir)) {
            rmdir(DIR_STORAGE . $this->dir);
        }
    }
}