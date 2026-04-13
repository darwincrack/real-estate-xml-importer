<?php

class REIP_Property_Importer {



    private $xml_urls = array(

        1 => 'http://propertyfeedv3.kyero.com/?99df5617d324ec8faca75981bfcfdf1789e060bf',

        2 => 'https://xml.tmgrupoinmobiliario.com/xmlKyero.php',

        3 => 'https://xml.redsp.net/file/506/23x465s0951/general-zone-1-kyero.xml',
        4 => 'https://app.vendomia.es/feed/xml/all/125/edb8840caae1b65487d74f8e9729aaca'

    );



    private $xml_url;

    private $max_properties = 2;

    private $max_images = 2;

    private $ajax = true;

    private $xml_type = 1;

    private $counter_repeats = 0;





    public function __construct($ajax) {

        

        $this->ajax = $ajax;

        if($ajax){



            // Obtener y validar max_properties

            $this->max_properties = isset($_POST['max_properties']) ? intval($_POST['max_properties']) : $this->max_properties;

            $this->max_images = isset($_POST['max_images']) ? intval($_POST['max_images']) : $this->max_images;

            $this->xml_type = isset($_POST['xml_type']) ? intval($_POST['xml_type']) : $this->xml_type;



            

            add_action('wp_ajax_reip_ref_properties', array($this, 'ajax_ref_properties_callback'));

            add_action('wp_ajax_reip_import_properties', array($this, 'ajax_import_properties_callback'));

            add_action('wp_ajax_check_import_progress', array($this, 'check_import_progress_callback'));

        

        }else{

      

            $this->max_properties = isset($_GET['max_properties']) ? intval($_GET['max_properties']) : $this->max_properties;

            $this->max_images = isset($_GET['max_images']) ? intval($_GET['max_images']) : $this->max_images;

            $this->xml_type = isset($_GET['xml_type']) ? intval($_GET['xml_type']) : $this->xml_type;
            
            // $this->import_properties();

        }



        $this->xml_url = $this->xml_urls[$this->xml_type];





    }

    

public function import_properties() {

    $this->write_log("Iniciando importación XML (" . ($this->ajax ? 'AJAX' : 'CRON') . ")");

    $this->write_log("URL a importar: " . $this->xml_url);



    // Configuración inicial

    $stats = ['added' => 0, 'updated' => 0, 'deleted' => 0];

    $reip_ref = get_option('reip_ref');

    $ref_incluir = array_map('strtolower', explode(",", $reip_ref['incluir']));

    $max_retries = 5;

    $xml = null;

    $all_refs = [];

    $processed_refs = [];

    $properties_to_process = [];



    // Intento de obtención del XML con reintentos

    for ($retry = 0; $retry < $max_retries; $retry++) {

        $response = wp_remote_get($this->xml_url, ['timeout' => 60, 'sslverify' => false]);

        

        if (!is_wp_error($response)) {

            $xml = simplexml_load_string(wp_remote_retrieve_body($response));

            if ($xml) break;

        }

        

        $this->write_log('Error en intento ' . ($retry + 1) . ': ' . print_r($response, true));

        if ($retry < ($max_retries - 1)) sleep(2);

    }



    // Manejo de errores después de reintentos

    if (!$xml || is_wp_error($response)) {

        $error_message = is_wp_error($response) ? $response->get_error_message() : 'Error parseando XML';

        $this->handle_import_error($error_message);

        return $this->ajax_response($stats);

    }



    // Preparar límites de propiedades

    $total_properties = count($xml->property);

    $this->max_properties = ($this->max_properties < 1) ? $total_properties : $this->max_properties;

    

    // Recolectar todas las propiedades

    $all_properties = [];

    foreach ($xml->property as $property) {

        $current_ref = strtolower((string)$property->ref);

        $all_refs[] = $current_ref;

        $all_properties[] = $property;

    }



    // Separar propiedades en dos grupos

    $priority_properties = [];

    $regular_properties = [];

    

    foreach ($all_properties as $property) {

        $current_ref = strtolower((string)$property->ref);

        if (in_array($current_ref, $ref_incluir)) {

            $priority_properties[$current_ref] = $property; // Usamos ref como key para evitar duplicados

        } else {

            $regular_properties[] = $property;

        }

    }



    // Asegurar máximo de propiedades regulares (sin contar las prioritarias)

    $max_regular = max($this->max_properties - count($priority_properties), 0);

    $regular_to_process = array_slice($regular_properties, 0, $max_regular);



    // Combinar todas las propiedades a procesar

    $properties_to_process = array_merge(

        array_values($priority_properties), // Propiedades prioritarias primero

        $regular_to_process

    );



    // Actualizar contadores reales

    $actual_total = count($properties_to_process);

    set_transient('reip_import_total', $actual_total, HOUR_IN_SECONDS);

    set_transient('reip_import_current', 0, HOUR_IN_SECONDS);



    // Procesar propiedades

    foreach ($properties_to_process as $index => $property) {

        set_transient('reip_import_current', $index + 1, HOUR_IN_SECONDS);

        $current_ref = strtolower((string)$property->ref);

        

        // Registrar solo una vez por ref

        if (!in_array($current_ref, $processed_refs)) {

            $this->process_property($property, $stats, $reip_ref);

            $processed_refs[] = $current_ref;

        }

    }



    // Actualizar propiedades no incluidas (vendidas)

    $this->update_vendidas($all_refs);



    // Finalizar importación

    update_option('reip_last_import', [

        'date' => current_time('mysql'),

        'stats' => $stats

    ]);



    $this->write_log("Importación completada. Estadísticas: " . print_r($stats, true));

    return $this->ajax_response($stats);

}

    



    

    private function process_property($property, &$stats,&$reip_ref) {

        

        

                $ref_excluir = explode(",", $reip_ref['excluir']);



                if (in_array((string)$property->ref, $ref_excluir)) {

                    $this->write_log("esta en la lista de excluidos, ref: ".$property->ref);

                    return;

                } 

                



        

        $property_id = (string)$property->id;

        

        // Buscar si la propiedad ya existe

        $existing_property = get_posts(array(

            'post_type' => 'propiedad',

            'meta_key' => '_property_id',

            'meta_value' => $property_id,

            'posts_per_page' => 1

        ));

        



        $this->write_log("Procesando propiedad ID: " . $property_id);

        

        $this->write_log("Procesando propiedad REF: " . (string)$property->ref);




        if($this->xml_type==4){
            $title = (string)$property->name;

        }else{
            $title = ($property->type== 'Apartment') ? 'Apartamento' : $property->type.' en '.$property->town.', '.$property->province.' province';

        }

        $post_data = array(

            'post_title' => ucfirst((string) $title),

            'post_content' => (string)$property->desc->es,

            'post_type' => 'propiedad',

            'post_status' => 'publish'

        );

        

        if (empty($existing_property)) {



            // Nueva propiedad

            $post_id = wp_insert_post($post_data);

            $stats['added']++;

            $this->write_log("Propiedad añadida: " .  $title . " POST ID: " . $post_id. " Property ID: " . $property_id);    

        } else {

            // Actualizar propiedad existente

            $post_data['ID'] = $existing_property[0]->ID;

            wp_update_post($post_data);

            $post_id = $existing_property[0]->ID;

            $stats['updated']++;

            $this->write_log("Propiedad actualizada: " .  $title . " POST ID: " . $existing_property[0]->ID. " Property ID: " . $property_id);    



        }





        $features = $property->features->feature;

        

        $feature_quality = $this->get_feature_safely($features, 0);

        $feature_pool = $this->get_feature_safely($features, 11);

        $feature_location = $this->get_feature_safely($features, 1).', '.$this->get_feature_safely($features, 2).', '.$this->get_feature_safely($features, 3).', '.$this->get_feature_safely($features, 4);

        $feature_outside = $this->get_feature_safely($features, 6).', '.$this->get_feature_safely($features, 7);

        $feature_garage = $this->get_feature_safely($features, 8).', '.$this->get_feature_safely($features, 9).', '.$this->get_feature_safely($features, 10);

        $feature_interior_other = $this->get_feature_safely($features, 12).', '.$this->get_feature_safely($features, 13);

        $feature_interior_kitchen = $this->get_feature_safely($features, 5);





        // Actualizar meta datos

        update_post_meta($post_id, '_property_id', $property_id);

        update_post_meta($post_id, '_property_leasehold', (string)$property->leasehold);

        update_post_meta($post_id, '_property_type', (string)$property->type);

        update_post_meta($post_id, '_property_ref', (string)$property->ref);

        update_post_meta($post_id, '_property_new_build', (string)$property->new_build);

        update_post_meta($post_id, '_property_town', (string)$property->town);

        update_post_meta($post_id, '_property_province', (string)$property->province);

        update_post_meta($post_id, '_property_postcode', (string)$property->postcode);

        update_post_meta($post_id, '_property_price_freq', (string)$property->price_freq);

        update_post_meta($post_id, '_property_price', (float)$property->price);

        update_post_meta($post_id, '_property_bedrooms', (int)$property->beds);

        update_post_meta($post_id, '_property_bathrooms', (int)$property->baths);

        update_post_meta($post_id, '_property_pool', (int)$property->pool);

        update_post_meta($post_id, '_property_feature_quality', (string)$feature_quality ?? '');

        update_post_meta($post_id, '_property_feature_pool', (string)$feature_pool ?? '');

        update_post_meta($post_id, '_property_feature_location', (string)$feature_location ?? '');

        update_post_meta($post_id, '_property_feature_outside', (string)$feature_outside ?? '');

        update_post_meta($post_id, '_property_feature_garage', (string)$feature_garage ?? '');

        update_post_meta($post_id, '_property_feature_interior_other', (string)$feature_interior_other ?? '');

        update_post_meta($post_id, '_property_feature_interior_kitchen', (string)$feature_interior_kitchen ?? '');

        update_post_meta($post_id, '_property_built', (string)$property->surface_area->built ?? '');

        update_post_meta($post_id, '_property_plot', (string)$property->surface_area->plot ?? '');

        update_post_meta($post_id, '_property_vendida', 'en venta');



        



        // Interior features



        // Guardar el tipo de propiedad como término de taxonomía

        if (!empty($property->type)) {

            $property_type = (string)$property->type;

            

            // Crear array de términos



            if($this->xml_type == 1){

                $property_terms = array($property_type, 'Propiedades en Reventa');



            }else if($this->xml_type == 2){

                $property_terms = array($property_type, 'Propiedades en Reventa dos');

            }else if ($this->xml_type == 3){

                $property_terms = array($property_type, 'Propiedades de nueva Construcción');

            }else{
                $property_terms = array($property_type, 'Oportunidades Inmobiliarias');
            }

            

            // Verificar si el término principal ya existe

            $term = get_term_by('slug', $property_type, 'property_type');

            

            if (!$term) {

                // Si no existe, lo creamos

                wp_insert_term($property_type, 'property_type');

            }

            

            // Asociamos todos los términos a la propiedad

            wp_set_object_terms($post_id, $property_terms, 'property_type');

        }



        // Procesar ubicación

        if (!empty($property->location)) {

            wp_set_object_terms($post_id, (string)$property->location, 'property_location');

        }

        

        // Procesar imágenes

        if (!empty($property->images)) {

       

            

            if(empty($existing_property)){

                $this->write_log('Procesando imágenes para propiedad que no existe aun ID: ' . $property_id);

                $this->process_images($post_id, $property->images); 

            }else{

                $this->write_log('Ya la propiedad existe y no se procesan imagenes ID: ' . $property_id);



            }

           

        }

    }

    

    

    private function update_vendidas($property_refs_xml) {

    $taxonomy_name = 'property_type';

    $term_name = 'vendidas';

    

    // Determinar el slug según el tipo de XML

    switch ($this->xml_type) {

        case 1:

            $slug_property_type = 'propiedades-en-reventa';

            break;

        case 2:

            $slug_property_type = 'propiedades-en-reventa-dos';

            break;

        case 3:

            $slug_property_type = 'propiedades-de-nueva-construccion';

            break;
        case 4:
            $slug_property_type = 'oportunidades-inmobiliarias';
            break;

        default:

            $slug_property_type = 'propiedades-en-reventa';

    }



    // Obtener todas las referencias de WordPress en minúsculas

    $args = array(

        'post_type' => 'propiedad',

        'posts_per_page' => -1,

        'tax_query' => array(

            array(

                'taxonomy' => 'property_type',

                'field' => 'slug',

                'terms' => $slug_property_type

            )

        ),

        'fields' => 'ids'

    );



    $propiedades_query = new WP_Query($args);

    $wordpress_refs = [];



    if ($propiedades_query->have_posts()) {

        foreach ($propiedades_query->posts as $post_id) {

            $ref = strtolower(get_post_meta($post_id, '_property_ref', true));

            if (!empty($ref)) {

                $wordpress_refs[$post_id] = $ref;

            }

        }

        wp_reset_postdata();

    }



    // Convertir array XML a minúsculas para coincidencia exacta

    $xml_refs_lower = array_map('strtolower', (array)$property_refs_xml);



    // Encontrar diferencias (refs en WordPress que no están en XML)

    $refs_faltantes = array_diff(array_values($wordpress_refs), $xml_refs_lower);



    // Obtener IDs de propiedades a actualizar

    $ids_a_actualizar = array_keys(array_intersect($wordpress_refs, $refs_faltantes));



    // Crear término si no existe

    $term = term_exists($term_name, $taxonomy_name);

    if (!$term) {

        $term = wp_insert_term(

            $term_name,

            $taxonomy_name,

            array(

                'description' => 'Propiedades vendidas',

                'slug' => 'vendidas'

            )

        );

    }



    // Actualizar propiedades si hay resultados

    if (!empty($ids_a_actualizar)) {

        $term_id = is_array($term) ? $term['term_id'] : $term->term_id;

        

        foreach ($ids_a_actualizar as $post_id) {

            // Añadir término sin eliminar otros existentes

            wp_set_post_terms(

                $post_id,

                array($term_id),

                $taxonomy_name,

                true

            );

            

            update_post_meta($post_id, '_property_vendida', 'vendida');

            $this->write_log("Propiedad ID $post_id marcada como vendida");

        }

        

        $this->write_log("Total propiedades actualizadas: " . count($ids_a_actualizar));

    } else {

        $this->write_log("No se encontraron propiedades para marcar como vendidas");

    }

}

    

    private function process_images($post_id, $images) {

        $this->write_log('Iniciando process_images para post_id: ' . $post_id);

        

        if (empty($images->image)) {

            $this->write_log('No hay imágenes para procesar');

            return;

        }



        $this->write_log('Número de imágenes encontradas: ' . count($images->image));

        $uploaded_images = array();

        

        $images_array = (array)$images;

        $this->write_log('Images array antes de slice: ' . print_r($images_array, true));

        $images_array = array_slice($images_array, $this->max_images);

        $this->write_log('Images array despues de slice: ' . print_r($images_array, true));



        // Contador para limitar las imágenes

        $image_count = 0;

        $i = 0;

        foreach ($images->image as $index => $image) {



                    // Obtener la URL del subelemento 'url'

                    $image_url = (string)$image->url;

                    $image_id = (string)$image['id'];



            if ($image_count >= $this->max_images) {



                    $pending_images[] = array(

                        'url' => $image_url,

                        'id' => (int)$image_id,

                        'order' => $i // +1 porque la primera imagen ya fue procesada

                    );

       

                $this->write_log('Guardando las imágenes pendientes, post_id: ' . $post_id);

                $this->write_log('Pending images: ' . print_r($pending_images, true));



                // Guardar las imágenes pendientes si hay alguna

                if (!empty($pending_images)) {

                    update_post_meta($post_id, '_pending_images', $pending_images);

                }





               

            }else{

                

        

                $gallery_data = array();

                $this->write_log('Procesando imagen ' . $image_id . ': ' . $image_url);



                // Verificar si la URL es válida

                if (!filter_var($image_url, FILTER_VALIDATE_URL)) {

                    $this->write_log('URL de imagen no válida: ' . $image_url);

                    continue;

                }else{

                    $this->write_log('URL de imagen válida: ' . $image_url);

                }



                $upload = $this->upload_image_from_url($image_url, $post_id);

                

                if (!is_wp_error($upload)) {

                    $attachment_id = wp_insert_attachment(array(

                        'post_mime_type' => $upload['type'],

                        'post_title' => sanitize_file_name(basename($image_url)),

                        'post_content' => '',

                        'post_status' => 'inherit'

                    ), $upload['file'], $post_id);



                    if (!is_wp_error($attachment_id)) {

                        // Generar metadatos de la imagen y miniaturas

                        require_once(ABSPATH . 'wp-admin/includes/image.php');

                        wp_update_attachment_metadata(

                            $attachment_id,

                            wp_generate_attachment_metadata($attachment_id, $upload['file'])

                        );

                        

                        $uploaded_images[] = $attachment_id;



                        $gallery_data['image_url'][]  = wp_get_attachment_url($attachment_id);









                        

                        // Establecer la primera imagen como destacada

                        if ($image_count === 0) {

                            set_post_thumbnail($post_id, $attachment_id);

                            $this->write_log('estableciendo imagen destacada ' . $image_id . ': ' . $image_url);



                        }else{

                            $this->write_log('no estableciendo imagen destacada ' . $image_id . ': ' . $image_url);

                        }









                        // Incrementar el contador de imágenes

                        $image_count = $image_count + 1;



                        $this->write_log('Incrementar el contador de imágenes '. $image_count);

                    }else{

                        $this->write_log('Error al insertar la imagen');

                    }





                }else{

                    // Añadir log detallado del error

                    $this->write_log('Error al subir la imagen: ' . $image_url);

                    $this->write_log('Código de error: ' . $upload->get_error_code());

                    $this->write_log('Mensaje de error: ' . $upload->get_error_message());

                    $this->write_log('Datos de error: ' . print_r($upload->get_error_data(), true));



                }









            }



            $i = $i+ 1;

        }

        

        // Guardar los IDs de todas las imágenes para la galería

        if (!empty($uploaded_images)) {

            update_post_meta($post_id, '_property_gallery', $uploaded_images);

            update_post_meta($post_id, 'gallery_data', $gallery_data);



        }



        $this->write_log('finalizando process_images para post_id: ' . $post_id);

    }

    

    

    

    

    



    public function upload_image_from_url($url, $post_id) {

        // Obtener el contenido de la imagen

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {

            return $response;

        }



        $image_data = wp_remote_retrieve_body($response);

        if (empty($image_data)) {

            return new WP_Error('image_download_failed', 'No se pudo descargar la imagen');

        }



        // Crear un nombre de archivo único

        $filename = basename($url);

        $upload_dir = wp_upload_dir();

        $unique_filename = wp_unique_filename($upload_dir['path'], $filename);

        $filepath = $upload_dir['path'] . '/' . $unique_filename;



        // Guardar la imagen en el servidor

        file_put_contents($filepath, $image_data);



        // Obtener el tipo de archivo

        $wp_filetype = wp_check_filetype($filename);



        // Devolver un array con la información necesaria

        return array(

            'file' => $filepath,

            'type' => $wp_filetype['type'],

            'url' => $upload_dir['url'] . '/' . $unique_filename

        );

    }

    

    

    private function get_feature_safely($features, $index) {

        if ((is_array($features) || $features instanceof Traversable) && isset($features[$index])) {

            return $features[$index];

        }

        return null;

    }



    public function check_import_progress_callback() {

        $total = get_transient('reip_import_total');

        $current = get_transient('reip_import_current');



       // $this->write_log('Total: ' . $total);

       // $this->write_log('Current: ' . $current);



        if ($total && $current !== false) {

            wp_send_json_success(array(

                'total' => $total,

                'current' => $current

            ));

        } else {

            wp_send_json_error();

        }

    }



    public function ajax_import_properties_callback() {

        $this->write_log('Iniciando ajax_import_properties_callback');

        $this->write_log('POST recibido: ' . print_r($_POST, true));

        $this->write_log('Nonce recibido: ' . (isset($_POST['security']) ? $_POST['security'] : 'no hay nonce'));

        

        try {

            // Verificar nonce usando check_ajax_referer

            if (!check_ajax_referer('reip_import_now', 'security', false)) {

                $this->write_log('Verificación de nonce fallida');

                $this->write_log('Nonce esperado: ' . wp_create_nonce('reip_import_now'));

                wp_send_json_error(array(

                    'message' => 'Error de seguridad: verificación fallida',

                    'received_nonce' => $_POST['security'] ?? 'no nonce'

                ));

                return;

            }



            // Verificar permisos

            if (!current_user_can('manage_options')) {

                $this->write_log('Usuario sin permisos');

                wp_send_json_error(array(

                    'message' => 'No tienes permisos para realizar esta acción.'

                ));

                return;

            }



            $this->write_log('-------------Iniciando importación xml_type '.$this->xml_type.'-------------------');

            $result = $this->import_properties();

            $this->write_log('Resultado de importación: ' . print_r($result, true));

            

            if ($result['success']) {

                wp_send_json_success($result);

            } else {

                wp_send_json_error($result);

            }

            

        } catch (Exception $e) {

            $this->write_log('Excepción en ajax_import_properties: ' . $e->getMessage());

            wp_send_json_error(array(

                'message' => 'Error: ' . $e->getMessage()

            ));

        }

    }





    

        public function ajax_ref_properties_callback() {

            



        $this->write_log('Iniciando ajax_ref_properties_callback');

        $this->write_log('POST recibido: ' . print_r($_POST, true));

        $this->write_log('Nonce recibido: ' . (isset($_POST['security']) ? $_POST['security'] : 'no hay nonce'));

        

        try {

            // Verificar nonce usando check_ajax_referer

            if (!check_ajax_referer('reip_import_now', 'security', false)) {

                $this->write_log('Verificación de nonce fallida');

                $this->write_log('Nonce esperado: ' . wp_create_nonce('reip_import_now'));

                wp_send_json_error(array(

                    'message' => 'Error de seguridad: verificación fallida',

                    'received_nonce' => $_POST['security'] ?? 'no nonce'

                ));

                return;

            }



            // Verificar permisos

            if (!current_user_can('manage_options')) {

                $this->write_log('Usuario sin permisos');

                wp_send_json_error(array(

                    'message' => 'No tienes permisos para realizar esta acción.'

                ));

                return;

            }



            $excluir_ref = isset($_POST['excluir_ref']) ? $_POST['excluir_ref'] : '';

            $incluir_ref = isset($_POST['incluir_ref']) ? $_POST['incluir_ref'] : '';

            

            

            update_option('reip_ref', array(

                        'excluir' => $excluir_ref,

                        'incluir' => $incluir_ref

            ));

                    

             wp_send_json_success();



            



        } catch (Exception $e) {

            $this->write_log('Excepción en ajax_ref_properties: ' . $e->getMessage());

            wp_send_json_error(array(

                'message' => 'Error: ' . $e->getMessage()

            ));

        }

    }

    

    

    // Métodos auxiliares para manejar respuestas

    private function ajax_response($stats) {

        if (!$this->ajax) return;

        

        return [

            'success' => true,

            'stats' => $stats,

            'message' => 'Importación completada'

        ];

    }

    

    private function handle_import_error($message) {

        $this->write_log($message);

        set_transient('reip_import_total', 0, HOUR_IN_SECONDS);

        set_transient('reip_import_current', 0, HOUR_IN_SECONDS);

        

        if ($this->ajax) {

            return ['success' => false, 'message' => $message];

        } else {

            exit($message);

        }

    }

    

    

    private function write_log($message) {

        $log_file = "xml_importer_".date('Y-m-d').".log";

        $log_file_path = '/home/wooyuhhw/jonathansuarezr.com/wp-content/plugins/real-estate-xml-importer/logs/' . $log_file;

        $timestamp = date('Y-m-d H:i:s');

        file_put_contents($log_file_path, "[$timestamp] $message\n", FILE_APPEND);  

        

    }



}