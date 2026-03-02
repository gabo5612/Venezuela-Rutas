<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>
<body <?php body_class('bg-background-dark text-slate-100 font-display antialiased overflow-x-hidden'); ?>>

<?php get_template_part('parts/nav', 'menu'); ?>
