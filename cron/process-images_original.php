<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Cargamos WordPress
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Configuración de límites
set_time_limit(0);
ini_set('memory_limit', '512M');

// Log para debugging
$log_file = "/home/wooyuhhw/jonathansuarezr.com/wp-content/plugins/real-estate-xml-importer/logs/xml-image-processing_".date('Y-m-d').".log";
function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

write_log('Iniciando proceso de imágenes');

// Obtenemos propiedades con imágenes pendientes
global $wpdb;
$properties = $wpdb->get_results(
    "SELECT post_id, meta_value 
    FROM $wpdb->postmeta 
    WHERE meta_key = '_pending_images' 
    LIMIT 5"  // Procesamos 5 propiedades por ejecución
);

if (empty($properties)) {
    write_log('No hay imágenes pendientes para procesar');
    exit;
}

// Instanciamos el importador
$importer = new REIP_Property_Importer(false);

foreach ($properties as $property) {
    write_log("Procesando imágenes para propiedad ID: {$property->post_id}");
    
    $pending_images = maybe_unserialize($property->meta_value);
    if (empty($pending_images)) continue;

    $uploaded_images = array();
    $max_images = 10; // Tu límite de imágenes (ahora será para imágenes adicionales)
    $image_count = 0;

    foreach ($pending_images as $image_data) {
        if ($image_count >= $max_images) break;

        write_log("Procesando imagen adicional: {$image_data['url']}");
        
        $upload = $importer->upload_image_from_url($image_data['url'], $property->post_id);

        if (!is_wp_error($upload)) {
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name(basename($image_data['url'])),
                'post_content' => '',
                'post_status' => 'inherit'
            ), $upload['file'], $property->post_id);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                wp_update_attachment_metadata(
                    $attachment_id,
                    wp_generate_attachment_metadata($attachment_id, $upload['file'])
                );

                $uploaded_images[] = $attachment_id;
                $image_count++;
                write_log("Imagen adicional procesada exitosamente: {$attachment_id}");
            } else {
                write_log("Error al crear attachment: " . $attachment_id->get_error_message());
            }
        } else {
            write_log("Error al subir imagen: " . $upload->get_error_message());
        }
    }

    if (!empty($uploaded_images)) {
        // Obtener galería existente (si hay)
        $existing_gallery_data = get_post_meta($property->post_id, 'gallery_data', true);

        if (!is_array($existing_gallery_data)) {
            $existing_gallery_data = array('image_url'=>array());
        }

        if (!empty($uploaded_images)) {
            $new_images = array_map(function($attachment_id) {
            $url = wp_get_attachment_url($attachment_id);
           
            if (!$url) {
                write_log("Error: No se pudo obtener URL para attachment ID: $attachment_id");
                return false;
            }
            return $url;
            }, $uploaded_images);

            $new_images = array_filter($new_images);
            $existing_gallery_data['image_url'] = array_merge(
            $existing_gallery_data['image_url'],
            $new_images
            );
        }

        update_post_meta($property->post_id, 'gallery_data', $existing_gallery_data);
    
    }

    // Eliminamos el meta de imágenes pendientes
    delete_post_meta($property->post_id, '_pending_images');
    write_log("Finalizado procesamiento para propiedad ID (POST_ID): {$property->post_id}");
}

write_log('Proceso finalizado'); 