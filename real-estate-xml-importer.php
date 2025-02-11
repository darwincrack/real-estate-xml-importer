<?php
/*
Plugin Name: Importador de Propiedades Inmobiliarias
Description: Importa propiedades desde XML de Kyero
Version: 1.0
Author: Darwin Cedeño
*/

if (!defined('ABSPATH')) exit;

// Definir constantes
define('REIP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REIP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar archivos necesarios
require_once REIP_PLUGIN_DIR . 'includes/class-property-importer.php';
require_once REIP_PLUGIN_DIR . 'includes/class-property-frontend.php';
require_once REIP_PLUGIN_DIR . 'includes/property-gallery.php';
require_once REIP_PLUGIN_DIR . 'admin/admin-menu.php';


// Cargar las clases necesarias
//require_once REIP_PLUGIN_DIR . 'includes/class-property-importer.php';

// Inicializar el plugin
function reip_init() {
    global $reip_importer;
    
    // Instanciar la clase principal
    if (!isset($reip_importer)) {
        $reip_importer = new REIP_Property_Importer(true);
    }
    
    return $reip_importer;
}

// Asegurarnos de que la clase se instancie temprano
add_action('plugins_loaded', 'reip_init');



// Registrar CPT al iniciar WordPress
add_action('init', 'reip_register_post_type');

function reip_register_post_type() {
   $labels = array(
        'name'               => _x('Propiedades', 'post type general name', 'real-estate-xml-importer'),
        'singular_name'      => _x('Propiedad', 'post type singular name', 'real-estate-xml-importer'),
        'menu_name'          => __('Propiedades XML', 'real-estate-xml-importer'),
        'name_admin_bar'     => __('Propiedad', 'real-estate-xml-importer'),
        'add_new'            => __('Añadir Nueva', 'real-estate-xml-importer'),
        'add_new_item'       => __('Añadir Nueva Propiedad', 'real-estate-xml-importer'),
        'edit_item'          => __('Editar Propiedad', 'real-estate-xml-importer'),
        'view_item'          => __('Ver Propiedad', 'real-estate-xml-importer'),
        'search_items'       => __('Buscar Propiedades', 'real-estate-xml-importer'),
        'not_found'          => __('No se encontraron propiedades', 'real-estate-xml-importer'),
        'not_found_in_trash' => __('No hay propiedades en la papelera', 'real-estate-xml-importer')
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-building',
        'rewrite'             => array('slug' => 'propiedad'),
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
        'show_in_rest'        => true // Habilita Gutenberg
    );

    register_post_type('propiedad', $args);

    // Taxonomía
    $taxonomy_labels = array(
        'name'              => _x('Categorías', 'taxonomy general name', 'real-estate-xml-importer'),
        'singular_name'     => _x('Categoría', 'taxonomy singular name', 'real-estate-xml-importer'),
        'search_items'      => __('Buscar Categorías', 'real-estate-xml-importer'),
        'all_items'         => __('Todas las Categorías', 'real-estate-xml-importer'),
        'parent_item'       => __('Categoría Padre', 'real-estate-xml-importer'),
        'parent_item_colon' => __('Categoría Padre:', 'real-estate-xml-importer'),
        'edit_item'         => __('Editar Categoría', 'real-estate-xml-importer'),
        'update_item'       => __('Actualizar Categoría', 'real-estate-xml-importer'),
        'add_new_item'      => __('Añadir Nueva Categoría', 'real-estate-xml-importer'),
        'new_item_name'     => __('Nuevo Nombre de Categoría', 'real-estate-xml-importer'),
        'menu_name'         => __('Categorías', 'real-estate-xml-importer'),
    );

    $taxonomy_args = array(
        'hierarchical'      => true,
        'labels'            => $taxonomy_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'categoria-propiedad')
    );

    register_taxonomy('property_type', 'propiedad', $taxonomy_args);
}

// Activación del plugin
register_activation_hook(__FILE__, 'reip_activate_plugin');

/*function reip_activate_plugin() {
    // Programar la tarea cron
    if (!wp_next_scheduled('reip_import_properties')) {
        wp_schedule_event(time(), 'daily', 'reip_import_properties');
    }
    
    // Actualizar reglas de reescritura
    flush_rewrite_rules();
}*/

// Desactivación del plugin
register_deactivation_hook(__FILE__, 'reip_deactivate_plugin');

function reip_deactivate_plugin() {
    wp_clear_scheduled_hook('reip_import_properties');
    flush_rewrite_rules();
}

// Agregar campos personalizados
add_action('add_meta_boxes', 'reip_add_meta_boxes');

function reip_add_meta_boxes() {
    add_meta_box(
        'property_details',
        'Detalles de la Propiedad',
        'reip_property_details_callback',
        'propiedad',
        'normal',
        'high'
    );
}


// Registrar la query var personalizada
function reip_register_query_vars($vars) {
    $vars[] = 'property_search'; // Añade el parámetro a las query vars
    return $vars;
}
add_filter('query_vars', 'reip_register_query_vars');


// Añadir columnas personalizadas a la lista de propiedades
function agregar_columnas_personalizadas($columns) {
    // Agrega las nuevas columnas
    $columns['precio'] = 'Precio';
    $columns['provincia'] = 'Provincia';
    $columns['categorias'] = 'Categorias';
    $columns['referencia'] = 'Referencia';

    // Opcional: Cambiar el orden de las columnas
    $custom_columns = array(
        'cb' => $columns['cb'], // Checkbox
        'title' => $columns['title'], // Título del post
        'referencia' => $columns['referencia'],
        'precio' => $columns['precio'],
        'provincia' => $columns['provincia'],
        'categorias' => $columns['categorias'],
        'date' => $columns['date'], // Fecha
    );

    return $custom_columns;
}
add_filter('manage_propiedad_posts_columns', 'agregar_columnas_personalizadas');

// Mostrar datos en las columnas personalizadas
function mostrar_datos_columnas_personalizadas($column, $post_id) {
    switch ($column) {
        case 'precio':
            $precio = get_post_meta($post_id, '_property_price', true); // Obtiene el campo personalizado "precio"
            echo $precio ? esc_html(number_format($precio, true).' €') : '—';
            break;
        case 'referencia':
            $referencia = get_post_meta($post_id, '_property_ref', true); 
            echo $referencia ? esc_html($referencia, true) : '—';
            break;
        case 'provincia':
            $provincia = get_post_meta($post_id, '_property_province', true); // Obtiene el campo personalizado "ubicacion"
            echo $provincia ? esc_html($provincia) : '—';
            break;
        case 'categorias':

            $terms = get_the_terms($post_id, 'property_type');

            if (!empty($terms) && !is_wp_error($terms)) {
                $term_links = array();
                
                // Generar enlaces para cada término
                foreach ($terms as $term) {
                    $term_link = esc_url(admin_url('edit.php?property_type=' . $term->slug . '&post_type=propiedad'));
                    $term_links[] = '<a href="' . $term_link . '">' . esc_html($term->name) . '</a>';
                }
    
                // Mostrar los enlaces separados por comas
                echo join(', ', $term_links);
            } else {
                echo '—'; // Mostrar guion si no hay términos
            }

            break;
    }
}
add_action('manage_propiedad_posts_custom_column', 'mostrar_datos_columnas_personalizadas', 10, 2);




function reip_property_details_callback($post) {
    wp_nonce_field('reip_property_details', 'reip_property_details_nonce');
   
    $property_id = get_post_meta($post->ID, '_property_id', true);
    $price = get_post_meta($post->ID, '_property_price', true);
    $bedrooms = get_post_meta($post->ID, '_property_bedrooms', true);
    $bathrooms = get_post_meta($post->ID, '_property_bathrooms', true);
    $pool = get_post_meta($post->ID, '_property_pool', true);
    $feature_quality = get_post_meta($post->ID, '_property_feature_quality', true);
    $feature_pool = get_post_meta($post->ID, '_property_feature_pool', true);
    $feature_location = get_post_meta($post->ID, '_property_feature_location', true);
    $feature_outside = get_post_meta($post->ID, '_property_feature_outside', true);
    $feature_garage = get_post_meta($post->ID, '_property_feature_garage', true);
    $feature_interior_other = get_post_meta($post->ID, '_property_feature_interior_other', true);
    $feature_interior_kitchen = get_post_meta($post->ID, '_property_feature_interior_kitchen', true);
    $price_freq = get_post_meta($post->ID, '_property_price_freq', true);
    $ref = get_post_meta($post->ID, '_property_ref', true);
    $new_build = get_post_meta($post->ID, '_property_new_build', true);
    $town = get_post_meta($post->ID, '_property_town', true);
    $province = get_post_meta($post->ID, '_property_province', true);
    $postcode = get_post_meta($post->ID, '_property_postcode', true);
    $leasehold = get_post_meta($post->ID, '_property_leasehold', true);
    $built = get_post_meta($post->ID, '_property_built', true);
    $plot = get_post_meta($post->ID, '_property_plot', true);
    $vendida = get_post_meta($post->ID, '_property_vendida', true);


  
  ?>
    <style>
        .reip-meta-box {
            max-width: 800px;
        }
        .reip-meta-box p {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .reip-meta-box label {
            flex: 0 0 200px;
            padding-right: 15px;
        }
        .reip-meta-box input {
            flex: 0 0 300px;
            height: 30px;
        }
        .reip-meta-box .features-title {
            font-weight: bold;
            margin: 20px 0 10px;
        }
    </style>
    <div class="reip-meta-box">
        <p>
            <label for="property_id">ID de Propiedad:</label>
            <input type="text" id="property_id" name="_property_id" value="<?php echo esc_attr($property_id); ?>" >
        </p>
        <p>
            <label for="property_ref">Referencia:</label>
            <input type="text" id="property_ref" name="_property_ref" value="<?php echo esc_attr($ref); ?>">
        </p>
        
        <p>
            <label for="property_id">Status de venta:</label>
            <input type="text" id="property_vendida" name="_property_vendida" value="<?php echo esc_attr($vendida); ?>" >
        </p>

        <p>
            <label for="property_price_freq">Frecuencia de Precio:</label>
            <input type="text" id="property_price_freq" name="_property_price_freq" value="<?php echo esc_attr($price_freq); ?>">
        </p>
        <p>
            <label for="property_price">Precio:</label>
            <input type="number" id="property_price" name="_property_price" value="<?php echo esc_attr($price); ?>">
        </p>



        <p>
            <label for="property_new_build">Nuevo:</label>
            <input type="text" id="property_new_build" name="_property_new_build" value="<?php echo esc_attr($new_build); ?>">
        </p>
        <p>
            <label for="property_leasehold">Arrendamiento:</label>
            <input type="text" id="property_leasehold" name="_property_leasehold" value="<?php echo esc_attr($leasehold); ?>">
        </p>
        <p>
            <label for="property_town">Población:</label>
            <input type="text" id="property_town" name="_property_town" value="<?php echo esc_attr($town); ?>">
        </p>
        <p>
            <label for="property_province">Provincia:</label>
            <input type="text" id="property_province" name="_property_province" value="<?php echo esc_attr($province); ?>">
        </p>
        <p>
            <label for="property_postcode">Código Postal:</label>
            <input type="text" id="property_postcode" name="_property_postcode" value="<?php echo esc_attr($postcode); ?>">
        </p>
        <p>
            <label for="property_bedrooms">Habitaciones:</label>
            <input type="number" id="property_bedrooms" name="_property_bedrooms" value="<?php echo esc_attr($bedrooms); ?>">
        </p>
        <p>
            <label for="property_bathrooms">Baños:</label>
            <input type="number" id="property_bathrooms" name="_property_bathrooms" value="<?php echo esc_attr($bathrooms); ?>">
        </p>
        <p>
            <label for="property_pool">Piscina:</label>
            <input type="number" id="property_pool" name="_property_pool" value="<?php echo esc_attr($pool); ?>">
        </p>
        <p><strong>Features:</strong></p>

        <p>
            <label for="quality">Quality:</label>
            <input type="text" id="property_feature_quality" name="_property_feature_quality" value="<?php echo esc_attr($feature_quality); ?>">
        </p>

        <p>
                <label for="pool">Pool:</label>
                <input type="text" id="property_feature_pool" name="_property_feature_pool" value="<?php echo esc_attr($feature_pool); ?>">
        </p>
        <p>
            <label for="location">Location:</label>
            <input type="text" id="property_feature_location" name="_property_feature_location" value="<?php echo esc_attr($feature_location); ?>">
        </p>
        <p>
            <label for="outside">Outside:</label>
            <input type="text" id="property_feature_outside" name="_property_feature_outside" value="<?php echo esc_attr($feature_outside); ?>">
        </p>
        <p>
            <label for="garage">Garage:</label>
            <input type="text" id="property_feature_garage" name="_property_feature_garage" value="<?php echo esc_attr($feature_garage); ?>">
        </p>
        <p>
            <label for="quality">Interior features (otros):</label>
            <input type="text" id="property_feature_interior_other" name="_property_feature_interior_other" value="<?php echo esc_attr($feature_interior_other); ?>">
        </p>
        <p>
            <label for="kitchen">Interior features (cocina):</label>
            <input type="text" id="property_feature_interior_kitchen" name="_property_feature_interior_kitchen" value="<?php echo esc_attr($feature_interior_kitchen); ?>">
        </p>

        <p>
            <label for="quality">Superficie construida (built):</label>
            <input type="text" id="property_built" name="_property_built" value="<?php echo esc_attr($built); ?>">
        </p>
        <p>
            <label for="kitchen">Superficie parcela (plot):</label>
            <input type="text" id="property_plot" name="_property_plot" value="<?php echo esc_attr($plot); ?>">
        </p>
    </div>
    <?php
}

// Guardar campos personalizados
add_action('save_post_propiedad', 'reip_save_propiedad_details');

function reip_save_propiedad_details($post_id) {
    if (!isset($_POST['reip_property_details_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['reip_property_details_nonce'], 'reip_property_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $fields = array(
        '_property_id',
        '_property_price', 
        '_property_bedrooms', 
        '_property_bathrooms',
        '_property_pool',
        '_property_feature_quality',
        '_property_feature_pool',
        '_property_feature_location',
        '_property_feature_outside',
        '_property_feature_garage',
        '_property_feature_interior_other',
        '_property_feature_interior_kitchen',
        '_property_type',
        '_property_ref',
        '_property_new_build',
        '_property_town',
        '_property_province',
        '_property_postcode',
        '_property_price_freq',
        '_property_vendida'
    );
    
    

    
    foreach ($fields as $field) {
        
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

}

// Inicializar el frontend
$property_frontend = new REIP_Property_Frontend();

// Agregar estilos y scripts
add_action('admin_enqueue_scripts', 'reip_admin_scripts');