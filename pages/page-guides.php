<?php
/**
 * Template Name: Guide Directory
 * Redirects to the guide CPT archive which has the map + grid.
 */
wp_redirect( get_post_type_archive_link('guide') ?: home_url('/guide/') );
exit;
