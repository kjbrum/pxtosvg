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

if ( ! class_exists( 'PXtoSVG' ) ) :

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
        require_once( 'includes/pxtosvg-shortcodes.php' );

        // Require our admin files
        if ( is_admin() ) {
            // Require the px2svg converter
            require_once( 'includes/Converter.php' );

            // Require the admin functionality
            require_once( 'admin/pxtosvg-admin.php' );
        }

        // Create our plugin page
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
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
    public function create_general_page() { ?>
        <div class="wrap">
            <h2>PX to SVG</h2>

            <?php
                // Check if the form has been submitted
                $this->handle_raster_upload();
                $this->handle_svg_upload();
            ?>

            <h3 class="title">Convert an Image</h3>
            <p>This will convert a raster image to an SVG to be used with the <code>[svg]</code> shortcode.</p>
            <form  method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pxtosvg-raster-upload'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="raster_image">Raster Image:<sup>*</sup></label></th>
                            <td><input type="file" id="raster_image" name="raster_image" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_svg_filename"><strong>Filename:</strong></label></th>
                            <td>
                                <input type="text" id="new_svg_filename" name="new_svg_filename">
                                <p class="description">This will set the filename of the uploaded image. Default is the current filename.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="threshold"><strong>Color Threshold (0-255):</strong></label>
                            </th>

                            <td>
                                <input type="number" name="threshold" id="threshold" value="0" min="0" max="255">
                                <p class="description">Color threshold determines whether similar colors are treated as the same color when creating the SVG. Default is 0.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Convert Image') ?>
            </form>

            <hr>

            <h3 class="title">Upload an SVG</h3>
            <p>This will upload an SVG to be used with the <code>[svg]</code> shortcode.</p>
            <form  method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pxtosvg-svg-upload'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="svg_file">SVG:<sup>*</sup></label></th>
                            <td><input type="file" id="svg_file" name="svg_file" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="svg_filename"><strong>Filename:</strong></label></th>
                            <td>
                                <input type="text" id="svg_filename" name="svg_filename">
                                <p class="description">This will set the filename of the uploaded SVG. Default is the current filename.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Upload SVG') ?>
            </form>
        </div>
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

            $image = $_FILES['raster_image'];

            // Upload our image to the WP media library
            $uploaded = media_handle_upload('raster_image', 0);

            // Check for uploading errors
            if( is_wp_error( $uploaded ) ) {
                echo '<div id="message" class="error">
                    <p>'.$uploaded->get_error_message().'</p>
                </div>';

                return;
            } else {
                $this->convert_px_to_svg( $uploaded );
                echo '<div id="message" class="updated notice is-dismissible">
                    <p>Image has been successfully converted to an SVG. <a href="link-to-svg">View SVG</a></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>';
            }
        }
    }

    /**
     *  Handle the uploading of an SVG
     *
     *  @return  void
     */
    public function handle_svg_upload() {
        // Check if the file appears on the _FILES array
        if( isset( $_FILES['svg_file'] ) ) {

            // Check for any errors
            if( !$_FILES['svg_file']['error'] ) {

                // Check for nonce
                check_admin_referer('pxtosvg-svg-upload');

                $svg_file = $_FILES['svg_file'];
                $ext = pathinfo($svg_file['name'], PATHINFO_EXTENSION);

                // Check for the correct extension
                if( $ext != 'svg' ) {
                    echo '<div id="message" class="error"><p>You must choose an SVG file to be uploaded.</p></div>';

                    return;
                }

                // Set where the SVG will be written
                $upload_dir = wp_upload_dir();
                $output_dir = $upload_dir['basedir'].'/svg/';

                // Save the SVG with the correct filename
                if( $_POST['svg_filename'] )
                    $uploaded = move_uploaded_file( $svg_file['tmp_name'], $output_dir.str_replace( ' ', '-', $_POST['svg_filename'] ).'.svg' );
                else
                    $uploaded = move_uploaded_file( $svg_file['tmp_name'], $output_dir.$svg_file['name'] );

                // Check for uploading errors
                if( $uploaded ) {
                    echo '<div id="message" class="updated notice is-dismissible">
                        <p>SVG has been successfully uploaded. <a href="link-to-svg">View SVG</a></p>
                        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                    </div>';
                } else {
                    echo '<div id="message" class="error"><p>There was an error while uploading the SVG.</p></div>';

                    return;
                }
            } else {
                echo '<div id="message" class="error"><p>'.$svg_file['error'].'</p></div>';

                return;
            }
        }
    }

    /**
     *  Handle converting the raster image to an SVG
     *
     *  @param   int   $raster_id  The ID of the image attachment
     *
     *  @return  void
     */
    public function convert_px_to_svg( $raster_id ) {
        // Get the attachment
        $file = get_post( $raster_id );

        // Initialize our converter
        $converter = new Converter();

        // Get the url of the attachment
        $url = wp_get_attachment_url( $raster_id );

        // Set where the SVG will be written
        $upload_dir = wp_upload_dir();
        $output_dir = $upload_dir['basedir'].'/svg/';

        if( !file_exists( $output_dir ) ) {
            mkdir( $output_dir, 0755, true );
        }

        try {
            // Generate the SVG
            $converter->loadImage( $url );

            // Set the threshold
            $converter->setThreshold( $_POST['threshold'] );

            // Save the SVG
            if( $_POST['new_svg_filename'] )
                $output = $converter->saveSVG( $output_dir.str_replace( ' ', '-', $_POST['new_svg_filename'] ).'.svg' );
            else
                $output = $converter->saveSVG( $output_dir.$file->post_title.'.svg' );

            // // Allows us access to the file system
            // WP_Filesystem();
            // // Write output as `.svg`
            // $output = $converter->loadImage($url)->generateSVG();
            // file_put_contents($output_dir.$file->post_title.'.svg', $output);
        } catch( Exception $e ){
            echo '<div id="message" class="error"><p>'.$e->getMessage().'</p></div>';

            return;
        }
    }
}

function pxtosvg() {
    global $pxtosvg;

    if ( ! isset( $pxtosvg ) ) {
        $pxtosvg = new PXtoSVG();
    }

    return $pxtosvg;
}

// Initialize
pxtosvg();

endif;

