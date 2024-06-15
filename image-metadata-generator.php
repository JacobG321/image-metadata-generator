<?php
/*
Plugin Name: Image Metadata Generator
Plugin URI: https://github.com/JacobG321/image-metadata-generator
Description: A plugin that generates metadata for images.
Version: 0.0.1
Author: Jacob Gruber
Author URI: https://jgruber.dev
*/


if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

use JacobGruber\ImageMetadataGenerator\ImageMetadataGenerator;

ImageMetadataGenerator::init();
