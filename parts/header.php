<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!--
    Material Symbols: if cached in localStorage → inject inline (zero network, zero flash).
    If not cached → add <link> dynamically. Never both at the same time.
  -->
  <script>
  (function(){
    var ICONS_URL='https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block';
    try{
      var c=localStorage.getItem('vr_icons');
      if(c){var s=document.createElement('style');s.textContent=c;document.head.appendChild(s);return;}
    }catch(e){}
    var l=document.createElement('link');l.rel='stylesheet';l.href=ICONS_URL;document.head.appendChild(l);
  }());
  </script>
  <?php wp_head(); ?>
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
<script src="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Bree+Serif&amp;display=swap" rel="stylesheet"/>
</head>
<body <?php body_class('bg-background-dark text-slate-100 font-display antialiased overflow-x-hidden'); ?>>

<?php get_template_part('parts/nav', 'menu'); ?>
