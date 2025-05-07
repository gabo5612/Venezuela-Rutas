<section>
    <div id="map" style="width: 100%; height: 600px;"></div>

    <script>
    var rutas = [];
    <?php
    $args = array(
        'post_type'      => 'rutas',
        'posts_per_page' => 10,
    );

    $rutas_query = new WP_Query($args);

    if ($rutas_query->have_posts()) :
        while ($rutas_query->have_posts()) : $rutas_query->the_post();

            $puntos = get_field('puntos');        // Repeater con lat/lng
            $nombre = get_the_title();            // Título de la ruta
            $blog = get_field('blog_entry');      // Relación al post del blog
            $imagen_url = get_field('imagen');    // URL directa de la imagen

            if ($puntos) :
                $ruta_puntos = [];
                foreach ($puntos as $punto) {
                    $lat = $punto['latitud'];
                    $lng = $punto['longitud'];
                    if (!empty($lat) && !empty($lng)) {
                        $ruta_puntos[] = [$lat, $lng];
                    }
                }

                if (count($ruta_puntos) > 0) {
                    $ruta_obj = array(
                        'nombre' => $nombre,
                        'puntos' => $ruta_puntos,
                        'blog_url' => $blog ? get_permalink($blog) : '',
                        'imagen_url' => $imagen_url ? $imagen_url : ''
                    );
                    echo "rutas.push(" . json_encode($ruta_obj) . ");\n";
                }

            endif;

        endwhile;
        wp_reset_postdata();
    else :
        echo "console.error('No se encontraron rutas.');";
    endif;
    ?>
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (rutas.length === 0) {
            console.error('No hay rutas para mostrar.');
            return;
        }

        var map = L.map('map').setView([0, 0], 2);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        rutas.forEach(function(ruta) {
            var puntosRuta = ruta.puntos;
            var nombreRuta = ruta.nombre;
            var blogUrl = ruta.blog_url;
            var imagenUrl = ruta.imagen_url;

            var polyline = L.polyline(puntosRuta, {
                color: '#FF0000',
                weight: 4,
                opacity: 0.7
            }).addTo(map);

            map.fitBounds(polyline.getBounds());

            var inicio = puntosRuta[0];

            var popupHtml = `<strong>${nombreRuta}</strong><br>`;

            if (imagenUrl) {
                popupHtml += `<img src="${imagenUrl}" alt="${nombreRuta}" style="max-width: 100%; height: auto; margin-top: 5px; margin-bottom: 5px;"><br>`;
            }

            if (blogUrl) {
                popupHtml += `<a href="${blogUrl}" target="_blank">Ver entrada relacionada</a><br>`;
            }

            popupHtml += "Inicio de la Ruta";

            L.marker(inicio).addTo(map)
                .bindPopup(popupHtml);
        });
    });
    </script>
</section>
