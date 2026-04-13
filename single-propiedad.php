<?php

/**

 * The template for displaying all single posts

 *

 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post

 *

 * @package Blocksy

 */



get_header(); ?>



<style>

.property-details-container {

    max-width: 1200px;

    margin: 0 auto;

    padding: 20px;

    color: #fff;



}



.property-title {

    font-size: 2.5rem;

    color:#fff;

}



.property-price {

    font-size: 1.5rem;

    color:var(--theme-link-initial-color);

    font-weight: bold;

}



.property-meta {



    border-radius: 8px;

    margin-bottom: 20px;

}



.property-meta ul {

    list-style: none;

    padding: 0;

}



.property-meta li {

    margin-bottom: 10px !important;

    font-size: 1.1rem;

}



.property-content {

    margin-bottom: 20px;

}



.property-content h3 {

    font-size: 1.8rem;

    margin-bottom: 10px;

}



.property-map iframe {

    border-radius: 8px;

    margin-top: 20px;

}



.property-gallery {

    position: relative;

    max-width: 1200px;

    margin: 0 auto; /* Centrar la galería completa */

}











    body {

      background: #000;

      color: #000;

    }



  

    .gallery-container {

            display: grid;

            grid-template-columns: 1fr 1fr; /* Imagen principal ocupa 2 partes, miniaturas 1 parte */

            gap: 10px;

            max-width: 1200px;

            margin: 20px auto;

            align-items: stretch; /* Asegura que todo tenga el mismo alto */

        }

        .main-image {

            grid-row: span 2; /* La imagen principal ocupa todo el espacio vertical */

        }

        .mini-images {

            display: grid;

            grid-template-columns: 1fr 1fr; /* Organiza miniaturas en 2 columnas */

            grid-template-rows: 1fr 1fr; /* Organiza miniaturas en 2 filas */

            gap: 10px;

        }

        .gallery img {

            width: 100%;

            height: 100%;

            object-fit: cover; /* Ajusta la imagen al contenedor sin deformarla */

            border-radius: 8px;

            cursor: pointer;

            transition: transform 0.2s ease-in-out;

        }

        .gallery img:hover {

            transform: scale(1.05);

        }



        .more-images {

            position: relative;

            overflow: hidden;

        }

        .more-images img {

            filter: brightness(70%); /* Oscurece la imagen ligeramente para resaltar el texto */

        }

        .more-images span {

            position: absolute;

            top: 50%;

            left: 50%;

            transform: translate(-50%, -50%);

            color: white;

            font-size: 18px;

            font-weight: bold;

            background-color: rgba(0, 0, 0, 0.5); /* Fondo translúcido */

            padding: 5px 10px;

            border-radius: 8px;

            text-align: center;

        }

        

        

    .property-status-vendida{

        background: red;

        padding: 6px;

        border-radius: 10px;

        margin-left: 15px;

        color: white;

        text-transform: capitalize;

        

    }

</style>



 <div class="property-details-container">

     <?php while (have_posts()) : the_post(); ?>

     

     <?php  $property_vendida = get_post_meta(get_the_ID(), '_property_vendida', true);?>



     <div class="property-header">

             <h1 class="property-title"><?php the_title(); ?></h1> 

             <p class="property-price"><?php echo number_format(get_post_meta(get_the_ID(), '_property_price', true), 0, '', '.'); ?> €   <?php if (!empty($property_vendida) and $property_vendida === 'vendida') : ?><span class="property-status-vendida"><?php echo $property_vendida ?> </span><?php endif; ?></p>

         </div>





         <div class="gallery-container">

        

         <!-- Imagen principal -->

        <?php $featured_image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');?>

        <a class="main-image" data-fancybox="gallery" href="<?= $featured_image_url; ?>">

            <?php the_post_thumbnail('full', ['alt' => 'Imagen principal']); ?>



        </a>

        <!-- Miniaturas 2x2 -->

        <div class="mini-images">

        <?php

            $gallery_images = get_post_meta(get_the_ID(), 'gallery_data', true);

            $total_gallery = count( $gallery_images['image_url']);

            $rest_show_gallery = $total_gallery -4;

            

            

            if ($gallery_images) :

                $i = 0;

                foreach ($gallery_images['image_url'] as $image_url) :

                    if ($i <= 3):?>

                    <a <?= ($total_gallery > 3 and $i==3) ? "class='more-images'" : ''?> data-fancybox="gallery" href="<?php echo esc_url($image_url); ?>" >

                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title(); ?>">

                        <?= ($total_gallery > 3  and $i==3) ? '<span>+'.$rest_show_gallery.' imágenes más</span>' : ''?>

                    </a>

                    <?php else: ?>

                    <!-- Imágenes adicionales -->

                    <div style="display: none;">

                        <a data-fancybox="gallery" href="<?php echo esc_url($image_url); ?>">

                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title(); ?>">

                        </a>

                    </div>



                    <?php endif; ?>

            <?php

            $i++;

                endforeach;

            endif;

            ?>



        </div>

    </div>

 



 

         <div class="property-meta">

             <h3>Características</h3>

             <ul style="display: flex; gap: 20px; flex-wrap: wrap;">

                <li style="display: flex; align-items: center;">

                    <i class="fas fa-bed" style="margin-right: 10px;"></i>

                    <span><strong>Habitaciones:</strong> <?php echo get_post_meta(get_the_ID(), '_property_bedrooms', true); ?></span>

                </li>

                <li style="display: flex; align-items: center;">

                    <i class="fas fa-bath" style="margin-right: 10px;"></i>

                    <span><strong>Baños:</strong> <?php echo get_post_meta(get_the_ID(), '_property_bathrooms', true); ?></span>

                </li>

                <li style="display: flex; align-items: center;">

                    <i class="fas fa-swimming-pool" style="margin-right: 10px;"></i>

                    <span><strong>Piscina:</strong> <?php echo get_post_meta(get_the_ID(), '_property_pool', true); ?></span>

                </li>


                <?php $superficie_construida = get_post_meta(get_the_ID(), '_property_built', true); ?>


                <?php if(!empty($superficie_construida) and $superficie_construida !=0): ?> 

                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-swimming-pool" style="margin-right: 10px;"></i>

                            <span><strong>Superficie construida:</strong> <?php echo $superficie_construida; ?> m<sup>2</sup></span>

                        </li>

                <?php endif; ?>


<?php $superficie_parcela = get_post_meta(get_the_ID(), '_property_plot', true); ?>


<?php if(!empty($superficie_parcela) and $superficie_parcela !=0): ?>
                <li style="display: flex; align-items: center;">

                    <i class="fas fa-swimming-pool" style="margin-right: 10px;"></i>

                    <span><strong>Superficie parcela:</strong> <?php echo get_post_meta(get_the_ID(), '_property_plot', true); ?> m<sup>2</sup></span>

                </li>

<?php endif; ?>

                

            </ul>

            

            <?php 

            $_property_feature_quality = get_post_meta(get_the_ID(), '_property_feature_quality', true);

            $_property_feature_location = get_post_meta(get_the_ID(), '_property_feature_location', true);

            $_property_feature_outside= get_post_meta(get_the_ID(), '_property_feature_outside', true);

            $_property_feature_pool= get_post_meta(get_the_ID(), '_property_feature_pool', true);

             $_property_ref= get_post_meta(get_the_ID(), '_property_ref', true);

           ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">

                <div>

                    <ul style="display: flex; flex-direction: column; gap: 20px;">


                     <?php if(!empty($_property_ref)): ?>

                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-map-marker-alt" style="margin-right: 10px;"></i>

                            <span><strong>Referencia:</strong> <?php echo $_property_ref; ?></span>

                        </li>

                    <?php endif; ?>

                        

                    <?php if ( ! empty( $_property_feature_quality ) && ! has_term( 'oportunidades-inmobiliarias', 'property_type', get_the_ID() ) ) : ?>


                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-star" style="margin-right: 10px;"></i>

                            <span><strong>condición:</strong> <?php echo get_post_meta(get_the_ID(), '_property_feature_quality', true); ?></span>

                        </li>

                    <?php endif; ?>

                    <?php

                    

                    if(!empty($_property_feature_location) and trim($_property_feature_location) !=', , ,'): ?>

                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-map-marker-alt" style="margin-right: 10px;"></i>

                            <span><strong>Ubicación:</strong> <?php echo get_post_meta(get_the_ID(), '_property_feature_location', true); ?></span>

                        </li>

                    <?php endif; ?>

                    <?php if(!empty($_property_feature_outside) and trim($_property_feature_outside) !=','): ?>

                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-door-open" style="margin-right: 10px;"></i>

                            <span><strong>Outside:</strong> <?php echo get_post_meta(get_the_ID(), '_property_feature_outside', true); ?></span>

                        </li>

                    <?php endif; ?>

                    <?php if(!empty($_property_feature_pool)): ?>

                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-door-open" style="margin-right: 10px;"></i>

                            <span><strong>Piscina:</strong> <?php echo get_post_meta(get_the_ID(), '_property_feature_pool', true); ?></span>

                        </li>

                    <?php endif; ?>

                    </ul>

                </div>

                

                <div>

                    <ul style="display: flex; flex-direction: column; gap: 20px;">

                        



                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-home" style="margin-right: 10px;"></i>

                        </li>

                        <li style="display: flex; align-items: center;">

                            <i class="fas fa-chart-area" style="margin-right: 10px;"></i>

                        </li>

                    </ul>

                </div>

            </div>



         </div>

 







         <div class="property-content">

             <h3>Descripción</h3>

             <?php the_content(); ?>

         </div>

 

         <div class="property-map">

             <h3>Ubicación Aproximada</h3>

             <?php

             $property_town = get_post_meta(get_the_ID(), '_property_town', true);

             $property_province = get_post_meta(get_the_ID(), '_property_province', true);

             $property_postcode = get_post_meta(get_the_ID(), '_property_postcode', true);

            $direccion = $property_town.', '.$property_province.', '.trim($property_postcode);


         

            ?>

            <div id="map" style="height: 400px;"></div>



            <h3>Categorias</h3>



            <p><?php echo get_the_term_list(get_the_ID(), 'property_type', '', ', ', ''); ?></p>







            <script>



            document.addEventListener('DOMContentLoaded', function () {

        // Crear el mapa

        var map = L.map('map').setView([-6.8127, -66.9257], 13);



        // Agregar capa base (OpenStreetMap)

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {

            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'

        }).addTo(map);



        // Buscar la dirección y agregar un marcador
        var address = "<?= trim($direccion); ?>";


        var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address);



        fetch(url)

            .then(response => response.json())

            .then(data => {

                if (data.length > 0) {

                    var lat = data[0].lat;

                    var lon = data[0].lon;



                    var marker = L.marker([lat, lon]).addTo(map);

                    marker.bindPopup(address).openPopup();

                } else {

                    console.log("Dirección no encontrada");

                }

            })

            .catch(error => {

                console.error('Error:', error);

            });



        });

    </script>



         </div>

     <?php endwhile; ?>

 </div>

 

 <?php get_footer(); ?>