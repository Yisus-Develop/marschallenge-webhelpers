<?php
/**
 * Plugin Name: Shortcode – Grid de Integrantes ACF
 * Description: Shortcode para mostrar un grid de integrantes con imagen, nombre, cargo, emoji y país
 * Version: 1.0.4 (Alineación y Fondo Completo)
 * Text Domain: acf-integrantes-grid
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) exit;

function integrantes_grid_shortcode($atts) {
    // Atributos por defecto
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'field_name' => 'equipo',
        'subfield_name' => 'integrantes'
    ), $atts);

    $post_id = $atts['post_id'];
    $field_name = $atts['field_name'];
    $subfield_name = $atts['subfield_name'];

    // Obtener el valor del campo ACF
    // NO MODIFICADO: Lógica PHP para obtener datos
    $equipo = get_field($field_name, $post_id);
    
    // Texto de depuración envuelto para ser traducible
    if (!$equipo || !is_array($equipo) || !isset($equipo[$subfield_name]) || !is_array($equipo[$subfield_name])) {
        return '<!-- ' . esc_html__('No se encontraron integrantes', 'acf-integrantes-grid') . ' -->';
    }

    $integrantes = $equipo[$subfield_name];
    
    // Texto de depuración envuelto para ser traducible
    if (!is_array($integrantes) || empty($integrantes)) {
        return '<!-- ' . esc_html__('No hay integrantes en este equipo', 'acf-integrantes-grid') . ' -->';
    }
    // FIN: Lógica PHP para obtener datos

    ob_start();
    ?>
    <style>
        /* Variables y Reset Básico */
        :root {
            --color-primary: #63b3ed; /* Azul claro */
            --color-text-light: #ffffff;
            --color-background-card: #fff;
            --card-radius: 12px;
            --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --card-height: 420px; /* Altura fija para consistencia visual */
            --emblema-size: 60px; /* Tamaño total del área del emblema (50px imagen + 2*5px padding) */
            --emblema-offset: 20px; /* Margen izquierdo para el emblema */
        }
        
        .perfiles-contenedor {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 0;
            font-family: 'Inter', Arial, sans-serif; /* Usando Inter por defecto para consistencia */
        }
        
        .integrantes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Ajustado para mejor distribución */
            gap: 30px;
        }
        
        .tarjeta-perfil {
            /* Estilo de la tarjeta principal */
            background: var(--color-background-card);
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            height: var(--card-height);
        }
        
        .tarjeta-perfil:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        /* --- Etiqueta de Cargo (Superior, Centrada) --- */
        .etiqueta-cargo-wrapper {
            position: absolute; /* Posicionamiento absoluto sobre la imagen */
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
        }

        .etiqueta {
            /* Estilo de la banda superior */
            /* Ya no usa --color-primary directamente, se aplica inline */
            color: var(--color-text-light);
            text-align: center;
            padding: 10px 15px;
            font-size: 1em;
            font-weight: 600;
            /* Solo bordes superiores redondeados (la imagen ahora tiene los inferiores) */
            border-radius: var(--card-radius) var(--card-radius) 0 0; 
            display: block;
            width: 100%;
        }
        
        .datos-persona {
            /* Contenedor principal que aloja la imagen y la info. Ahora cubre toda la tarjeta. */
            position: relative;
            width: 100%;
            height: 100%; /* Ocupa el 100% de la tarjeta */
            top: 0; /* Elimina el desplazamiento superior */
        }
        
        .foto-perfil {
            width: 100%;
            height: 100%; /* Ocupa el 100% del contenedor de datos (toda la tarjeta) */
            object-fit: cover;
            display: block;
        }
        
        /* --- Contenedor de Información Superpuesta (Inferior) --- */
        .info-inferior-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 5; /* Por encima de la foto */
            padding: 20px;
            color: var(--color-text-light);
            /* Degradado de negro transparente a negro sólido en el fondo de la imagen */
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.0) 100%);
        }

        /* Contenedor Flex para la información de texto y bandera */
        .texto-info-wrapper {
            margin-top: 15px; /* Espacio debajo del emoji (que está flotando por encima) */
            text-align: left;
        }

        /* Estilo del Emoji/Emblema */
        .emblema {
            /* Posición del emoji */
            position: absolute;
            top: -45px; /* Subirlo por encima del texto, ajustado ligeramente */
            left: var(--emblema-offset);
            font-size: 3em; /* Para emojis unicode */
            line-height: 1;
            /* El ancho/alto del contenedor es importante para que el texto lo evite */
            width: var(--emblema-size);
            height: var(--emblema-size);
        }

        .emblema img, .emblema span {
            /* Si es una imagen (como en el PHP original) o span de fallback */
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white; /* Fondo blanco para que destaque */
            padding: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            display: block; /* Asegura que el span o img ocupen el espacio */
        }
        
        .nombre {
            font-size: 1.5em; /* Tamaño de fuente más grande para el nombre */
            font-weight: bold;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5); /* Sombra para mejor legibilidad */
            line-height: 1.2;
        }
        
        .ubicacion {
            font-size: 1em;
            color: #ccc; /* Gris claro */
            margin: 5px 0 0 0;
            display: flex;
            align-items: center;
            justify-content: flex-start; /* Alinear a la izquierda */
        }
        
        .bandera {
            width: 25px;
            height: 20px;
            margin-right: 8px;
            border-radius: 2px;
            box-shadow: 0 0 3px rgba(0,0,0,0.3);
        }

        /* Media Queries para Responsive */
        @media (max-width: 600px) {
            .integrantes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <div class="perfiles-contenedor">
        <div class="integrantes-grid">
            <?php foreach ($integrantes as $index => $integrante): ?>
                <?php if (is_array($integrante)): ?>
                    <?php 
                        // Obtener el color_cargo o usar el color primario por defecto (#63b3ed)
                        $cargo_color = isset($integrante['color_cargo']) && !empty($integrante['color_cargo']) 
                                    ? esc_attr($integrante['color_cargo']) 
                                    : '#63b3ed';
                    ?>
                    <div class="tarjeta-perfil">
                        <div class="datos-persona">
                            <!-- Etiqueta de Cargo (MOVIDA DENTRO DE datos-persona) -->
                            <?php if (isset($integrante['cargo']) && !empty($integrante['cargo'])): ?>
                                <div class="etiqueta-cargo-wrapper">
                                    <!-- APLICACIÓN DEL ESTILO EN LÍNEA -->
                                    <div class="etiqueta" style="background-color: <?php echo $cargo_color; ?>;">
                                        <?php echo esc_html($integrante['cargo']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                                // Determinar la URL de la foto o usar placeholder
                                $placeholder_text = esc_attr__('FOTO PERFIL', 'acf-integrantes-grid'); // Texto traducible para el placeholder
                                $foto_url = (isset($integrante['foto-integrante']) && !empty($integrante['foto-integrante'])) 
                                    ? esc_url($integrante['foto-integrante']) 
                                    : 'https://placehold.co/400x533/' . substr(hash('md5', $integrante['nombre'] ?? 'Integrante'), 0, 6) . '/ffffff?text=' . $placeholder_text;
                                $alt_text = esc_attr($integrante['nombre'] ?? 'Integrante');
                            ?>
                            <img class="foto-perfil"
                                src="<?php echo $foto_url; ?>"
                                onerror="this.src='https://placehold.co/400x533/63b3ed/ffffff?text=<?php echo esc_attr__('FOTO INTEGRANTE', 'acf-integrantes-grid'); ?>'"
                                alt="<?php echo $alt_text; ?>">
                            
                            <!-- Contenedor de Información Superpuesta -->
                            <div class="info-inferior-overlay">
                                <!-- Emoji/Emblema -->
                                <div class="emblema">
                                    <?php if (isset($integrante['emoji']) && !empty($integrante['emoji'])): ?>
                                        <img src="<?php echo esc_url($integrante['emoji']); ?>" alt="<?php esc_attr_e('Emblema del Integrante', 'acf-integrantes-grid'); ?>">
                                    <?php else: ?>
                                        <!-- Emoji por defecto si no hay imagen de emoji -->
                                        <span style="background:white; border-radius:50%; padding:5px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">👤</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Texto de Nombre y Ubicación. Alineado con el emoji flotante -->
                                <div class="texto-info-wrapper">
                                    <!-- Nombre -->
                                    <?php if (isset($integrante['nombre']) && !empty($integrante['nombre'])): ?>
                                        <p class="nombre">
                                            <?php echo esc_html($integrante['nombre']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- País/Ubicación -->
                                    <?php
                                    // Obtener el país desde la taxonomía 'country' del post actual
                                    $country_terms = get_the_terms($post_id, 'country');
                                    if ($country_terms && !is_wp_error($country_terms)):
                                        $country_name = esc_html($country_terms[0]->name);
                                        $country_slug = $country_terms[0]->slug;
                                        // Usar una URL genérica para banderas si ACF no la proporciona directamente
                                        $flag_url = 'https://marschallenge.space/wp-content/uploads/mc-geo/flags/' . $country_slug . '.svg';
                                    ?>
                                        <div class="ubicacion">
                                            <img class="bandera" src="<?php echo esc_url($flag_url); ?>" alt="<?php echo esc_attr__('Bandera de', 'acf-integrantes-grid') . ' ' . $country_name; ?>">
                                            <?php echo $country_name; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('integrantes_grid', 'integrantes_grid_shortcode');