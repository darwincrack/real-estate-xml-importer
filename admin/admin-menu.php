<?php
function reip_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=propiedad',
        'Importar Propiedades',
        'Importar XML',
        'manage_options',
        'reip-import',
        'reip_import_page'
    );
}
add_action('admin_menu', 'reip_admin_menu');

function reip_admin_scripts($hook) {
    error_log('Hook actual: ' . $hook);
    
    if ('propiedad_page_reip-import' !== $hook) {
        return;
    }

    error_log('Encolando scripts admin');
    
    $nonce = wp_create_nonce('reip_import_now');
    error_log('Nonce generado: ' . $nonce);
    
    wp_enqueue_script(
        'reip-admin-js',
        plugins_url('/assets/js/admin.js', dirname(__FILE__)),
        array('jquery'),
        time(),
        true
    );

    wp_localize_script('reip-admin-js', 'reipAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => $nonce
    ));
}
add_action('admin_enqueue_scripts', 'reip_admin_scripts');

function reip_import_page() {
  
  // Obtenemos propiedades con imágenes pendientes
global $wpdb;

$reip_ref = get_option('reip_ref');

$properties = $wpdb->get_results(
    "SELECT post_id, meta_value 
    FROM $wpdb->postmeta 
    WHERE meta_key = '_pending_images'"  // Procesamos 10 propiedades por ejecución
);


if (empty($properties)) {
    $post_img = 'No hay imágenes pendientes para procesar';

}else{
    
    $post_img =count($properties);
}



?>

    <div class="wrap reip-admin">
        <h1>Importar Propiedades desde XML</h1>
        
        
                 
         <?php if ($last_import = get_option('reip_last_import')): ?>
            <div class="reip-stats">
                <h3>Última importación: <?php echo esc_html($last_import['date']); ?></h3>
                <p>Propiedades añadidas: <?php echo intval($last_import['stats']['added']); ?></p>
                <p>Propiedades actualizadas: <?php echo intval($last_import['stats']['updated']); ?></p>
                <p>Propiedades eliminadas: <?php echo intval($last_import['stats']['deleted']); ?></p>
            </div>
        <?php endif; ?>  
        
        <p>-------------------------------</p>
        
        <h2>Importar Propiedades</h2>
        <div id="import-progress" class="notice notice-info" style="display:none;">
            <p>Iniciando importación, por favor espere...</p>
        </div>
        
        <form method="post" id="reip-import-form">
            <?php wp_nonce_field('reip_import_now'); ?>

            <div class="form-field" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px;">
                    XML a importar:
                </label>
                <input type="radio" id="url_type_full" name="xml_type" value="1" checked>
                <label for="xml_type_1">http://propertyfeedv3.kyero.com/?99df5617d324ec8faca75981bfcfdf1789e060bf (Propiedades en Reventa <a href="https://jonathansuarezr.com/categoria-propiedad/propiedades-en-reventa/" target="_blank">Ver</a> )</label><br>
                <input type="radio" id="url_type_partial" name="xml_type" value="2">
                <label for="xml_type_2">http://xml.tmgrupoinmobiliario.com/xmlKyero.php</label><br>
                <input type="radio" id="url_type_partial" name="xml_type" value="3">
                <label for="xml_type_3">https://xml.redsp.net/file/506/23x465s0951/general-zone-1-kyero.xml  (Propiedades de nueva Construcción <a href="https://jonathansuarezr.com/categoria-propiedad/propiedades-de-nueva-construccion/" target="_blank">Ver</a> )</label> 
            </div>

            <div class="form-field" style="margin-bottom: 20px;">
                <label for="max_properties" style="display: block; margin-bottom: 5px;">
                    Número máximo de propiedades a importar (0 para importar todas):
                </label>
                <input 
                    type="number" 
                    id="max_properties" 
                    name="max_properties" 
                    value="2" 
                    min="0" 
                    style="width: 100px;"
                >
            </div>

            <div class="form-field" style="margin-bottom: 20px;">
                <label for="max_images" style="display: block; margin-bottom: 5px;">
                    Número máximo de imagenes a importar por propiedad (0 para importar todas):
                </label>
                <input 
                    type="number" 
                    id="max_images" 
                    name="max_images" 
                    value="2" 
                    min="0" 
                    style="width: 100px;"
                >
            </div>


            <input type="submit" name="import_now" id="start-import" class="button button-primary" value="Importar Ahora">
        </form>
        
        
  
        
        
    <br><br>
    <h2>Excluir e incluir propiedades del XML de importación</h2>
    
    

        <div id="info-text"></div>
        
        <form method="post" id="reip-save-ref-form">
            <?php wp_nonce_field('reip_import_now'); ?>


            <div class="form-field" style="margin-bottom: 20px;">
                <label for="max_images" style="display: block; margin-bottom: 5px;">
                    Excluir referencias (separadas por coma):
                </label>
                <input type="text" id="excluir_ref" name="excluir_ref" value="<?=$reip_ref['excluir']; ?>">
            </div>
            
            
            <div class="form-field" style="margin-bottom: 20px;">
                <label for="max_images" style="display: block; margin-bottom: 5px;">
                    Incluir referencias (separadas por coma):
                </label>
                <input type="text" id="incluir_ref" name="incluir_ref" value="<?=$reip_ref['incluir']; ?>">
            </div>


            <input type="submit" name="save_ref_now" id="save_ref_now" class="button button-primary" value="Guardar">
        </form>
        
        
        

   
<br><br>
            <div class="reip-stats">
                <h3>Información para configurar las tareas programadas (CRON JOB)</h3>
                <p><strong>Importar XML</strong></p>
                <p>Se utiliza para recorrer todos los xml</p>
                <p>URL: <?php echo esc_url(get_site_url().'/wp-content/plugins/real-estate-xml-importer/cron/process-xml.php'); ?></p>
                <p>Puede recibir parametros de:</p>
                <p>- max_properties (el valor por defecto es 2): Número máximo de propiedades a importar (0 para importar todas)</p>
                <p>- max_images (el valor por defecto es 2): Número máximo de imágenes a importar por propiedad </p>
                <p>- xml_type (el valor por defecto es 1): Es el xml que se va a recorrer las opciones son:</p>
                <p>1 = http://propertyfeedv3.kyero.com/?99df5617d324ec8faca75981bfcfdf1789e060bf (propiedad segunda)</p>
                <p>2 = http://xml.tmgrupoinmobiliario.com/xmlKyero.php (propiedad segunda dos)</p>
                <p>3 = https://xml.redsp.net/file/506/23x465s0951/general-zone-1-kyero.xml (propiedad Nueva)</p>


                <p>ejemplo: <?php echo esc_url(get_site_url().'/wp-content/plugins/real-estate-xml-importer/cron/process-xml.php?max_properties=10&max_images=2'); ?></p>

                <br>
                <p><strong>IMAGENES:</strong> </p>
                <p>Se utiliza para procesar las imagenes de las propiedades que se han importando.</p>
                <p>Ejemplo: si al importar una propiedad con max_images = 3 pero esa propiedad tiene mas imagenes, el resto de imagenes se importaran en este proceso, se importaran como maximo 8 imagenes por propiedad.</p>

                <p> URL: <?php echo esc_url(get_site_url().'/wp-content/plugins/real-estate-xml-importer/cron/process-images.php'); ?></p>

                
                <h4>Cantidad de propiedades con imagenes pendientes de procesar:</h4>
                <p><?=  $post_img ?></p>

            </div>


   
    </div>
    <?php
}