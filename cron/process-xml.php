<?php
// Cargamos WordPress
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');
require_once('../includes/class-property-importer.php');

// Configuración de límites
set_time_limit(0);
ini_set('memory_limit', '512M');

// Log para debugging
$log_file = "/home/wooyuhhw/jonathansuarezr.com/wp-content/plugins/real-estate-xml-importer/logs/xml_importer_".date('Y-m-d').".log";
function write_logx($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

write_logx('--------------- Iniciando proceso de cron xml tipo: '.$_GET['xml_type'].'-------------------------------');

// Instanciamos el importador
$importer = new REIP_Property_Importer(false);

$importer->import_properties();

write_logx('----------------finalizando proceso de cron xml tipo: '.$_GET['xml_type'].'----------------------------');