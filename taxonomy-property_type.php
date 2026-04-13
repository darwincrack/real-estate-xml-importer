<?php get_header(); ?>

<?php
// Obtener todas las provincias únicas
$provincias = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = '_property_province' 
    ORDER BY meta_value ASC
");



// Obtener todas las poblaciones únicas
$poblaciones = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = '_property_town' 
    ORDER BY meta_value ASC
");

// Obtener todas las habitaciones únicas
$habitaciones = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = '_property_bedrooms' 
    AND meta_value != ''
    ORDER BY CAST(meta_value AS UNSIGNED) ASC
");

// Obtener todas las categorías de property_type
$property_types = get_terms(array(
    'taxonomy' => 'property_type',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC'
));
?>

<div class="container">

    <!-- Mostrar título de la página con capitalización adecuada -->

    <h1 class="page-title">

        <?php echo ucwords(single_term_title('', false)); ?>

    </h1>

    <p class="page-description"><?php echo term_description(); ?></p>



    <!-- Buscador para filtrar por provincia -->
    <?php
    // Obtener la URL de la taxonomía actual para mantenerla al hacer búsquedas
    $current_term = get_queried_object();
    $form_action = '';
    if ($current_term && isset($current_term->term_id)) {
        $form_action = get_term_link($current_term);
    }
    ?>

    <form method="get" action="<?php echo esc_url($form_action); ?>" class="property-search-form" id="property-search-form">

        <input type="text" name="property_search" placeholder="Buscar por título..." 
            value="<?php echo esc_attr(get_query_var('property_search', '')); ?>" class="search-input">

        <select name="property_province" class="search-select">
            <option value="">Todas las provincias</option>
            <?php foreach ($provincias as $provincia): if ($provincia == 'indefined' or empty($provincia)) continue; ?>
                <option value="<?php echo esc_attr($provincia); ?>" <?php selected($_GET['property_province'] ?? '', $provincia); ?>>
                    <?php echo esc_html($provincia); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="property_town" class="search-select">
            <option value="">Todas las poblaciones</option>
            <?php foreach ($poblaciones as $poblacion):  if ($poblacion == 'indefined' or empty($poblacion)) continue; ?>
                <option value="<?php echo esc_attr($poblacion); ?>" <?php selected($_GET['property_town'] ?? '', $poblacion); ?>>
                    <?php echo esc_html($poblacion); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_category" class="search-select">
            <option value="">Todas las categorías</option>
            <?php if (!empty($property_types) && !is_wp_error($property_types)): ?>
                <?php foreach ($property_types as $type): ?>
                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($_GET['filter_category'] ?? '', $type->slug); ?>>
                        <?php echo esc_html($type->name); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <select name="property_bedrooms" class="search-select">
            <option value="">Todas las habitaciones</option>
            <?php foreach ($habitaciones as $habitacion): 
                // Omitir valores vacíos o cero
                if (empty($habitacion) || $habitacion == '0') continue;
            ?>
                <option value="<?php echo esc_attr($habitacion); ?>" <?php selected($_GET['property_bedrooms'] ?? '', $habitacion); ?>>
                    <?php echo esc_html($habitacion); ?> habitaciones
                </option>
            <?php endforeach; ?>
        </select>



        <button type="submit" class="search-button">Buscar</button>
        <a href="<?php echo esc_url(get_term_link(get_queried_object())); ?>" class="clear-button">Limpiar filtros</a>

    </form>




    <div class="grid-container">

        <?php

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$search_term        = isset($_GET['property_search']) ? sanitize_text_field($_GET['property_search']) : '';
$selected_province  = isset($_GET['property_province']) ? sanitize_text_field($_GET['property_province']) : '';
$selected_town      = isset($_GET['property_town']) ? sanitize_text_field($_GET['property_town']) : '';
$selected_bedrooms  = isset($_GET['property_bedrooms']) ? sanitize_text_field($_GET['property_bedrooms']) : '';
$selected_type      = isset($_GET['filter_category']) ? sanitize_text_field($_GET['filter_category']) : '';

// Construir meta_query
$meta_query = array('relation' => 'AND');

if (!empty($selected_province)) {
    $meta_query[] = array(
        'key'     => '_property_province',
        'value'   => $selected_province,
        'compare' => '='
    );
}

if (!empty($selected_town)) {
    $meta_query[] = array(
        'key'     => '_property_town',
        'value'   => $selected_town,
        'compare' => '='
    );
}

if (!empty($selected_bedrooms)) {
    $meta_query[] = array(
        'key'     => '_property_bedrooms',
        'value'   => $selected_bedrooms,
        'compare' => '='
    );
}

// Obtener taxonomía actual si aplica
$current_taxonomy = get_queried_object();
$taxonomy_slug = $current_taxonomy ? $current_taxonomy->slug : '';

// Crear argumentos
$args = array(
    'post_type'      => 'propiedad',
    'posts_per_page' => 36,
    'paged'          => $paged,
    'meta_query'     => $meta_query,
);

// Agregar búsqueda por título si existe
if (!empty($search_term)) {
    $args['s'] = $search_term;
}

// Construir tax_query para combinar taxonomía actual + filtro del formulario
$tax_query_items = array();

// Si estamos en una taxonomía, mantenerla siempre
if (!empty($taxonomy_slug)) {
    $tax_query_items[] = $taxonomy_slug;
}

// Si hay filtro adicional desde el formulario y es diferente de la taxonomía actual, agregarlo
if (!empty($selected_type) && $selected_type !== $taxonomy_slug) {
    $tax_query_items[] = $selected_type;
}

// Si tenemos términos para filtrar, aplicarlos
if (!empty($tax_query_items)) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'property_type',
            'field'    => 'slug',
            'terms'    => $tax_query_items,
            'operator' => 'AND', // La propiedad debe tener TODOS los términos
        ),
    );
}

$query = new WP_Query($args);


 



        if ($query->have_posts()) :

            while ($query->have_posts()) : $query->the_post();



                // Obtener los valores de los campos personalizados

                $property_price = get_post_meta(get_the_ID(), '_property_price', true);

                $property_province = get_post_meta(get_the_ID(), '_property_province', true);

                $property_town = get_post_meta(get_the_ID(), '_property_town', true);

                $property_bedrooms = get_post_meta(get_the_ID(), '_property_bedrooms', true);

                $property_bathrooms = get_post_meta(get_the_ID(), '_property_bathrooms', true);

                $property_pool = get_post_meta(get_the_ID(), '_property_pool', true);

                $property_vendida = get_post_meta(get_the_ID(), '_property_vendida', true);

                





        ?>

                <div class="grid-item">

                    <a href="<?php the_permalink(); ?>" class="property-link">

                        <?php if (has_post_thumbnail()) : ?>

                            <div class="thumbnail">

                                <?php the_post_thumbnail('medium_large', array('class' => 'property-image')); ?>

                            </div>

                        <?php endif; ?>



                        <h2 class="property-title"><?php the_title(); ?></h2>



                        <!-- Mostrar precio con formato en euros -->

                        <?php if (!empty($property_price)) : ?>

                            <p class="property-price"><?php echo number_format($property_price, 0, '', '.'); ?> €     <?php if (!empty($property_vendida) and $property_vendida === 'vendida') : ?><span class="property-status-vendida"><?php echo $property_vendida ?> </span><?php endif; ?></p>

                        <?php endif; ?>

                        



                     



                        <!-- Mostrar extracto (limitado a 2 líneas) -->

                        <p class="property-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?></p>



                        <!-- Mostrar habitaciones, baños y piscina -->

                        <p class="property-details">

                            <?php if (!empty($property_bedrooms)) : ?>

                                <?php echo $property_bedrooms; ?> habitaciones |

                            <?php endif; ?>

                            <?php if (!empty($property_bathrooms)) : ?>

                                <?php echo $property_bathrooms; ?> baños |

                            <?php endif; ?>

                            <?php if (!empty($property_pool)) : ?>

                                <?php echo $property_pool == '1' ? 'Piscina incluida' : ''; ?>

                            <?php endif; ?>

                        </p>



                        <!-- Mostrar provincia -->

                        <?php if (!empty($property_province)) : ?>

                            <p class="property-province"><?php echo esc_html($property_town); ?>, <?php echo esc_html($property_province); ?> </p>

                        <?php endif; ?>

                    </a>

                </div>

        <?php

            endwhile;

        else :

            echo '<p>No se encontraron propiedades.</p>';

        endif;

        ?>



        <?php wp_reset_postdata(); ?>

    </div>



    <!-- Paginador -->

    <div class="pagination">

        <?php

        echo paginate_links(array(

            'total' => $query->max_num_pages,

            'current' => $paged,

            'format' => '?paged=%#%',

            'prev_text' => '« Anterior',

            'next_text' => 'Siguiente »',

        ));

        ?>

    </div>

</div>



<!-- Estilos CSS -->

<style>

    body {

        background-color: #1e1e1e;

        color: #fff;

        font-family: Arial, sans-serif;

    }



    .container {

        max-width: 1400px;

        margin: 0 auto;

        padding: 20px;

    }



    .page-title {

        font-size: 32px;

        text-align: center;

        margin-bottom: 20px;

        text-transform: capitalize;

    }



    .page-description {

        font-size: 18px;

        text-align: center;

        margin-bottom: 40px;

        color: #aaa;

    }



    .property-search-form {

        text-align: center;
        margin-bottom: 30px;
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;

    }


    .property-search-form input.search-input {
        flex: 1 1 200px;
        padding: 8px;
        min-width: 150px;
        max-width: 300px;
    }

    .property-search-form select.search-select {
        flex: 0 1 150px;
        padding: 8px;
    }

    .property-search-form .search-button {
        padding: 8px 15px;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .property-search-form .clear-button {
        padding: 8px 15px;
        white-space: nowrap;
        flex: 0 0 auto;
        display: inline-block;
        text-align: center;
    }





    .property-search-form input[type="text"] {

        padding: 10px;
        font-size: 16px;
        border: 1px solid #444;
        border-radius: 4px;
        background-color: #292929;
        color: #fff;

    }



    .property-search-form button {

        padding: 10px 20px;

        font-size: 16px;

        background-color: #f39c12;

        color: #fff;

        border: none;

        border-radius: 4px;

        cursor: pointer;

    }



    .property-search-form button:hover {

        background-color: #e67e22;

    }

    .property-search-form a.clear-button {

        padding: 10px 20px;

        font-size: 16px;

        background-color: #95a5a6;

        color: #fff;

        border: none;

        border-radius: 4px;

        text-decoration: none;

        cursor: pointer;

    }

    .property-search-form a.clear-button:hover {

        background-color: #7f8c8d;

    }



    .grid-container {

        display: grid;

        grid-template-columns: repeat(3, 1fr);

        gap: 20px;

    }



    .grid-item {

        background-color: #292929;

        border: 1px solid #333;

        border-radius: 8px;

        overflow: hidden;

        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);

        transition: transform 0.2s, box-shadow 0.2s;

    }



    .grid-item:hover {

        transform: translateY(-5px);

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);

    }



    .property-link {

        text-decoration: none;

        color: #fff;

        display: block;

    }



    .thumbnail {

        overflow: hidden;

        height: 200px;

    }



    .thumbnail img.property-image {

        width: 100%;

        height: 100%;

        object-fit: cover;

    }



    .property-title {

        font-size: 20px;

        font-weight: bold;

        margin: 15px 10px 10px;

    }



    .property-price {

        font-size: 18px;

        color: #f39c12;

        margin: 10px 10px;

        font-weight: bold;

    }



    .property-excerpt {

        font-size: 14px;

        margin: 10px;

        color: #ccc;

        overflow: hidden;

        display: -webkit-box;

        -webkit-line-clamp: 2;

        -webkit-box-orient: vertical;

        text-overflow: ellipsis;

    }



    .property-details {

        font-size: 14px;

        margin: 10px;

        color: #aaa;

    }



    .property-province {

        font-size: 14px;

        margin: 10px;

        color: #bbb;

    }



    .pagination {

        margin: 20px 0;

        text-align: center;

    }



    .pagination a,

    .pagination span {

        display: inline-block;

        margin: 0 5px;

        padding: 10px 15px;

        color: #fff;

        text-decoration: none;

        border: 1px solid #444;

        background: #333;

        border-radius: 4px;

    }



    .pagination a:hover {

        background: #f39c12;

        color: #fff;

    }



    .pagination .current {

        background: #f39c12;

        color: #fff;

        border-color: #f39c12;

    }

    

    .property-status-vendida{

        

        background: red;

        padding: 6px;

        border-radius: 10px;

        margin-left: 15px;

        color: white;

        text-transform: capitalize;

        

    }
    

/* Media query para desktop - asegurar que todo esté en una línea */
@media only screen and (min-width: 1200px) {

    .property-search-form {
        flex-wrap: nowrap;
    }

    .property-search-form input.search-input {
        flex: 1 1 250px;
        max-width: 350px;
    }

    .property-search-form select.search-select {
        flex: 0 1 160px;
    }

}

/* Media query para tablets */
@media only screen and (min-width: 769px) and (max-width: 1199px) {

    .property-search-form {
        flex-wrap: wrap;
        gap: 10px;
    }

    .property-search-form input.search-input {
        flex: 1 1 100%;
        max-width: 100%;
    }

    .property-search-form select.search-select {
        flex: 1 1 calc(25% - 10px);
        min-width: 140px;
    }

    .property-search-form .search-button,
    .property-search-form .clear-button {
        flex: 1 1 calc(50% - 5px);
    }

}

@media only screen and (max-width: 768px) {

    .grid-container {

        grid-template-columns: 1fr;

    }

}

/* Responsive para móviles - Buscador */
@media (max-width: 768px) {

    .property-search-form {
        flex-direction: column;
        align-items: stretch;
        flex-wrap: wrap;
    }

    .property-search-form input.search-input,
    .property-search-form select.search-select,
    .property-search-form .search-button,
    .property-search-form .clear-button {
        width: 100%;
        max-width: 100%;
    }
}
</style>

<script>
// Evitar enviar parámetros vacíos en el formulario de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('property-search-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Obtener todos los inputs y selects del formulario
            const inputs = form.querySelectorAll('input, select');
            
            inputs.forEach(function(input) {
                // Si el campo está vacío, deshabilitarlo temporalmente para que no se envíe
                if (!input.value || input.value === '') {
                    input.disabled = true;
                }
            });
        });
    }
});
</script>

<?php get_footer(); ?>

