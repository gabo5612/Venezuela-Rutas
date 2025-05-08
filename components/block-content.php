<section>
    <div id="map" style="width: 100%; height: 600px;"></div>

    <script>
        var routes = [];
        <?php
        $args = array(
            'post_type'      => 'routes',
            'posts_per_page' => 10,
        );

        $routes_query = new WP_Query($args);

        if ($routes_query->have_posts()) :
            while ($routes_query->have_posts()) : $routes_query->the_post();

                $points = get_field('points');
                $title = get_the_title();
                $blog = get_field('blog_entry');
                $image_url = get_field('image');

                if ($points) :
                    $route_points = [];
                    foreach ($points as $point) {
                        $lat = $point['latitude'];
                        $lng = $point['longitude'];
                        if (!empty($lat) && !empty($lng)) {
                            $route_points[] = [$lat, $lng];
                        }
                    }

                    // Obtener puntos de interés asociados como waypoints
                    $poi_waypoints = [];
                    $poi_posts = get_field('route_point_of_interest');
                    if (!empty($poi_posts)) {
                        foreach ((array) $poi_posts as $poi) {
                            $poi_lat = get_field('interest_latitude', $poi);
                            $poi_lng = get_field('interest_longitude', $poi);
                            if (!empty($poi_lat) && !empty($poi_lng)) {
                                $poi_waypoints[] = $poi_lat . ',' . $poi_lng;
                            }
                        }
                    }

                    if (count($route_points) > 0) {
                        $route_obj = array(
                            'title' => $title,
                            'points' => $route_points,
                            'blog_url' => $blog ? get_permalink($blog) : '',
                            'image_url' => $image_url ? $image_url : '',
                            'poi_waypoints' => $poi_waypoints
                        );
                        echo "routes.push(" . json_encode($route_obj) . ");\n";
                    }

                endif;

            endwhile;
            wp_reset_postdata();
        else :
            echo "console.error('No routes found.');";
        endif;
        ?>
    </script>

    <script>
        var pointsOfInterest = [];
        <?php
        $poi_args = array(
            'post_type'      => 'point-of-interest',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        $poi_query = new WP_Query($poi_args);

        if ($poi_query->have_posts()) :
            while ($poi_query->have_posts()) : $poi_query->the_post();
                $lat = get_field('interest_latitude');
                $lng = get_field('interest_longitude');
                $entry = get_field('interest_entry');
                $image = get_field('interest_image');

                if (!empty($lat) && !empty($lng)) {
                    $poi_obj = array(
                        'lat' => $lat,
                        'lng' => $lng,
                        'entry_url' => $entry ? get_permalink($entry) : '',
                        'image_url' => $image ? $image : '',
                        'title' => get_the_title()
                    );
                    echo "pointsOfInterest.push(" . json_encode($poi_obj) . ");\n";
                }
            endwhile;
            wp_reset_postdata();
        endif;
        ?>
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (routes.length === 0) {
                console.error('No routes to display.');
                return;
            }

            var map = L.map('map').setView([0, 0], 2);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            routes.forEach(function (route) {
                var routePoints = route.points;
                var routeTitle = route.title;
                var blogUrl = route.blog_url;
                var imageUrl = route.image_url;
                var poiWaypoints = route.poi_waypoints || [];

                var polyline = L.polyline(routePoints, {
                    color: '#FF0000',
                    weight: 4,
                    opacity: 0.7
                }).addTo(map);

                map.fitBounds(polyline.getBounds());

                var start = routePoints[0];
                var end = routePoints[routePoints.length - 1];

                let popupHtml = `<strong>${routeTitle}</strong><br>`;

                if (imageUrl) {
                    popupHtml += `<img src="${imageUrl}" alt="${routeTitle}" style="max-width: 100%; height: auto; margin-top: 5px; margin-bottom: 5px;"><br>`;
                }

                if (blogUrl) {
                    popupHtml += `<a href="${blogUrl}" target="_blank">View related blog post</a><br>`;
                }

                if (routePoints.length >= 2) {
                    const origin = start.join(',');
                    const destination = end.join(',');

                    const waypoints = poiWaypoints.join('|');

                    const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}&waypoints=${encodeURIComponent(waypoints)}`;

                    popupHtml += `<br><a href="${googleMapsUrl}" target="_blank" class="map-link-button">Open full route in Google Maps</a>`;
                }

                popupHtml += `<br>Route start`;

                L.marker(start).addTo(map).bindPopup(popupHtml);
            });

            // Show interest points on the map
            pointsOfInterest.forEach(function (poi) {
                var lat = parseFloat(poi.lat);
                var lng = parseFloat(poi.lng);

                if (!isNaN(lat) && !isNaN(lng)) {
                    var popupHtml = `<strong>${poi.title}</strong><br>`;

                    if (poi.image_url) {
                        popupHtml += `<img src="${poi.image_url}" alt="${poi.title}" style="max-width: 100%; height: auto; margin: 5px 0;"><br>`;
                    }

                    if (poi.entry_url) {
                        popupHtml += `<a href="${poi.entry_url}" target="_blank">Read more</a><br>`;
                    }

                    popupHtml += "Point of Interest";

                    L.marker([lat, lng], {
                        icon: L.icon({
                            iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
                            iconSize: [25, 25],
                            iconAnchor: [12, 25],
                            popupAnchor: [0, -25]
                        })
                    }).addTo(map).bindPopup(popupHtml);
                }
            });
        });
    </script>

    <style>
        .map-link-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 6px 10px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 5px;
        }

        .map-link-button:hover {
            background-color: #0056b3;
        }
    </style>
</section>
