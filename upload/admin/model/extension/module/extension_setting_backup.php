<?php
class ModelExtensionModuleExtensionSettingBackup extends Model {
    private $dir = 'esb';

    public function save($controller, $settings, $moduleId = 0) {
        if($this->config->get('module_extension_setting_backup_status')) {

            $type = trim(strrchr(dirname($controller), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
            $module = basename($controller, '.php');
            $json = json_encode($settings);
            $path = DIR_STORAGE . $this->dir . '/' . $type . '/' . $module;
            if($moduleId) {
                $path .= '/' . $moduleId;
            }
            mkdir($path, 0777, true);
            file_put_contents($path . '/' . time() . '.json', $json);

            if($this->config->get('module_extension_setting_backup_clean_interval')){
                $this->cleanUp($path);
            }
        }
    }

    private function cleanUp($path){
        $fileList = glob($path . '/*.json');
        foreach ($fileList as $filename) {

            $path_parts = pathinfo($filename);

            $date = new DateTime();
            $date->setTimestamp($path_parts['filename']);
            $now = new DateTime("now");

            if ($now->diff($date)->format('%a') > $this->config->get('module_extension_setting_backup_clean_interval')) {
                unlink($filename);
            }
        }
    }
}