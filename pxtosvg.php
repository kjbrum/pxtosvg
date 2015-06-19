<?php
/*
Plugin Name: PX to SVG
Version: 1.0
Description: Allow users to convert raster images to SVGs
Author: Kyle Brumm
Author URI: http://kylebrumm.com
Plugin URI: http://kylebrumm.com/pxtosvg
Text Domain: pxtosvg
Domain Path: /languages
*/

use Px2svg\Converter;

if ( ! class_exists('PXtoSVG') ) :

class PXtoSVG {
    var $settings;

    /**
     *  Construct our class
     */
    public function __construct() {
        $this->settings = array(
            'url'       => plugin_dir_url( __FILE__ ),
            'path'      => plugin_dir_path( __FILE__ )
        );


        // Require the shortcodes
        require_once('includes/pxtosvg-shortcodes.php');

        // Require our admin files
        if ( is_admin() ) {
            // Require the px2svg converter
            require_once('includes/converter.php');

            // Require the admin functionality
            require_once('admin/pxtosvg-admin.php');
        }

        // Create our plugin page
        add_action('admin_menu', array( $this, 'add_plugin_page' ));
    }

    /**
     * Add plugin page under "Tools"
     *
     *  @return  void
     */
    public function add_plugin_page() {
        add_management_page(
            'PX to SVG',
            'PX to SVG',
            'manage_options',
            'pxtosvg-general',
            array( $this, 'create_general_page' )
        );
    }

    /**
     * Management page callback
     *
     *  @return  void
     */
    public function create_general_page() {
        $this->handle_raster_upload();
    ?>
        <h1>PX to SVG</h1>

        <h3>Upload Image</h3>
        <p>Select the raster image you want to upload to convert to an SVG</p>

        <form  method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('pxtosvg-raster-upload'); ?>
            <input type='file' id='raster_image' name='raster_image'></input>
            <?php submit_button('Upload') ?>
        </form>
    <?php
    }

    /**
     *  Handle the uploading of an image
     *
     *  @return  void
     */
    public function handle_raster_upload() {

        // Check if the file appears on the _FILES array
        if( isset( $_FILES['raster_image'] ) ) {

            // Check for nonce
            check_admin_referer('pxtosvg-raster-upload');

            $pdf = $_FILES['raster_image'];

            $uploaded = media_handle_upload('raster_image', 0);

            // Error checking using WP functions
            if( is_wp_error($uploaded) ) {
                echo 'Error uploading file: ' . $uploaded->get_error_message();
            } else {
                echo 'File upload successful!';
                $this->convert_px_to_svg( $uploaded );
            }
        }
    }

    public function convert_px_to_svg( $raster_id ) {
        // Get the attachment
        $file = get_post($raster_id);

        // Initialize our converter
        $converter = new Converter();

        // Get the url of the attachment
        $url = wp_get_attachment_url($raster_id);

        // Set where the SVG will be written
        $upload_dir = wp_upload_dir();
        $output_dir = $upload_dir['basedir'].'/svg/';

        if(!file_exists($output_dir)) {
            mkdir($output_dir, 0755, true);
        }

        try {
            // Generate the SVG
            $output = $converter->loadImage($url)->generateSVG();

            // Compress the output
            $compressed = gzcompress($output);

            // This method is very improtant as it allows us access to the file system
            WP_Filesystem();

            // Write output as `.svg`
            file_put_contents($output_dir.$file->post_title.".svg", $compressed);
            // file_put_contents($output_dir.$file->post_title.".svg", $output);
        } catch(Exception $e){
            echo $e->getMessage() . "<br>";
            return;
        }
    }
}

function pxtosvg() {
    global $pxtosvg;

    if ( ! isset($pxtosvg) ) {
        $pxtosvg = new PXtoSVG();
    }

    return $pxtosvg;
}

// Initialize
pxtosvg();

endif;

