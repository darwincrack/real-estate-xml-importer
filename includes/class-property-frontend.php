<?php
class REIP_Property_Frontend {
    public function __construct() {
        add_shortcode('propiedades', [$this, 'properties_shortcode']);
        //add_action('init', [$this, 'register_property_post_type']);
       // add_action('init', [$this, 'register_rewrite_rules']);

       add_action('wp_enqueue_scripts', [$this,'enqueue_gallery_scripts']);

     //  add_action('wp_enqueue_scripts', [$this,'enqueue_gallery_custom_js']);

    }
    
    public function properties_shortcode($atts) {
        $atts = shortcode_atts([
            'cantidad' => 10
        ], $atts);
        
        // Implementar lgica para mostrar propiedades
        ob_start();
        // HTML aqu
        return ob_get_clean();
    }



    public function enqueue_gallery_scripts() {
        // leaflet.js
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array('jquery'), null, true);
    
        // Fancybox
        wp_enqueue_style('fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css');
        wp_enqueue_script('fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js', array('jquery'), null, true);
    }

  /*  public function register_rewrite_rules() {
        add_rewrite_rule(
            'propiedades-usadas/?$',
            'index.php?pagename=propiedades-usadas',
            'top'
        );
        
        add_rewrite_rule(
            'propiedad/([^/]+)/?$',
            'index.php?propiedad=$matches[1]',
            'top'
        );
        
        flush_rewrite_rules();
    }*/
    
    /*public function register_property_post_type() {
        $labels = array(
            'name'               => __('Properties', 'real-estate-xml-importer'),
            'singular_name'      => __('Property', 'real-estate-xml-importer'),
            'menu_name'          => __('Properties', 'real-estate-xml-importer'),
            'add_new'           => __('Add New', 'real-estate-xml-importer'),
            'add_new_item'      => __('Add New Property', 'real-estate-xml-importer'),
            'edit_item'         => __('Edit Property', 'real-estate-xml-importer'),
            'new_item'          => __('New Property', 'real-estate-xml-importer'),
            'view_item'         => __('View Property', 'real-estate-xml-importer'),
            'search_items'      => __('Search Properties', 'real-estate-xml-importer'),
            'not_found'         => __('No properties found', 'real-estate-xml-importer'),
            'not_found_in_trash'=> __('No properties found in trash', 'real-estate-xml-importer')
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'property'),
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_position'       => 5,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon'           => 'dashicons-building'
        );

        register_post_type('property', $args);
    }*/
} 