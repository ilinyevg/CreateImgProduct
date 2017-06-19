//Wordpress and WooCommerce
//Upload image and create digital product

<?php

function advanced_upload() {
    if ( !current_user_can( 'vendor' )  )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
     
    $result = '';
    
    if ( ! empty( $_POST ) ) {
         $result = advanced_upload_photos();
    }

    wp_enqueue_media();
    
	?>
        <h2>Upload Photos</h2> 
        <form action="" method="post" class="photos-upload-form" enctype="multipart/form-data">
            <?php wp_nonce_field('photos-upload'); ?>
            <p class="city-notice"> City:</p>
            <p><input type="text" name="tb_city" id="tb_city"></p>
            <p>(Enter the city where the photos were taken)</p>
            <p class="date-notice"> Date:</p>
            <p><input type="date" name="tb_date" id="tb_date" placeholder="format (mm/DD/yyyy)"></p>
            <p>(Enter the date when the photos were taken)</p>
            <p class="image-notice">Photos:</p>
            <p><input id="upload_image_button" type="button" class="button" value="<?php _e( 'Upload image' ); ?>" /></p>
            
            <input type="hidden" name="image_ids" id="image_ids">
            <input type="hidden" name="action" value="photos_upload">
            <hr>
            <p><input type="submit" class="SubmitBtn" value="Create album"></p>
            <p class="form-upload"></p>
        </form>
    
    <?php  $my_saved_attachment_post_id = get_option( 'media_selector_attachment_id', 0 ); ?>
    
    <script type='text/javascript'>
		jQuery( document ).ready( function( $ ) {
			// Uploading files
			var file_frame;
			var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
			var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this
			jQuery('#upload_image_button').on('click', function( event ){
				event.preventDefault();
			
        wp.media.model.settings.post.id = set_to_post_id;
				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: true,	// Set to true to allow multiple files to be selected
                    library: {
                    type: 'image'
                    }
				});
				// When an images is selected, run a callback.
				file_frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					//attachment = file_frame.state().get('selection').first().toJSON();
                    attachment = file_frame.state().get('selection').toJSON();
                    console.log(attachment);
					// Do something with attachment.id and/or attachment.url here

                    $.each(attachment, function (index, value) {
                        //$( '#image-preview' ).attr( 'src', value.url ).css( 'width', 'auto' );

                        var imgArray = [];   
                        if ($( '#image_ids' ).val()) {
                            imgArray = JSON.parse($( '#image_ids' ).val()); 
                        }
                            
                        imgArray.push(value.id ); 
                        $( '#image_ids' ).val(JSON.stringify(imgArray));

					    //$( '#image_ids' ).val( value.id );
                    });

					// Restore the main post ID
					wp.media.model.settings.post.id = wp_media_post_id;
				});
					// Finally, open the modal
					file_frame.open();
			});
			// Restore the main ID when the add media button is pressed
			jQuery( 'a.add_media' ).on( 'click', function() {
				wp.media.model.settings.post.id = wp_media_post_id;
			});
		});
	</script>
    <?php  
    
    echo $result ;
}

function advanced_upload_photos() {
    $result = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        $city = $_POST['tb_city'];
        $date = $_POST['tb_date'];
        $user = get_current_user_id();
        $imageIds = json_decode(stripslashes($_POST['image_ids']));;
        
        foreach ($imageIds as &$imgId) {
            $result .= create_product_by_imageid($imgId, $user, $city, $date);
        }
       
        $result .= '<div style="background-color:#ccffcc; color:green; margin:5px; padding:5px; border:1px solid black;">New photo album was created. </div>';
    }
    else {
        $result .= '<div style="background-color:#ccffcc; color:red; margin:5px; padding:5px; border:1px solid black;">Something is wrong. Please contact our support via menu option in the right. </div>';
    }

    return $result; 
}
 
function generatePhotoName($user_id) { 
    $result = substr(get_user_meta( $user_id, 'venuscode', true ), 0, -5);;
    $last_num = count_user_posts( $user_id, 'product' ) + 1;
    $qty = str_pad($last_num, 6, '0', STR_PAD_LEFT);
   
    return $result.$qty;
}

function create_product_by_imageid($attach_id, $user, $city, $date) {
    $product_title = generatePhotoName($user);
    $post = array(
        'post_author' => $user,
        'post_content' => '',
        'post_status' => "publish",
        'post_title' => $product_title,
        'post_parent' => '',
        'post_type' => "product", 
    );
    
    //Create post
    $post_id = wp_insert_post( $post, $wp_error );
    $files_bits = wp_get_attachment_image($attach_id, "full");

    if($post_id){ 
        // Get the path to the upload directory.
        $wp_upload_dir = wp_upload_dir();

        require_once(ABSPATH . 'wp-admin/includes/image.php');
  
        set_post_thumbnail( $post_id, $attach_id );
     }
     
     update_post_meta( $post_id, '_visibility', 'visible' ); 
     update_post_meta( $post_id, '_purchase_note', "" );  
     update_post_meta( $post_id, '_manage_stock', "no" );
     update_post_meta( $post_id, '_backorders', "no" );
     update_post_meta( $post_id, '_stock', "" );
 
    update_post_meta($post_id, '_sold_individually', "yes"); 
    update_post_meta($post_id, '_virtual', "yes"); 
    update_post_meta($post_id, '_downloadable', "yes"); 
    update_post_meta($post_id, '_regular_price', '10'); 
    update_post_meta($post_id, '_price', '10'); 
    
    $venus_code = get_user_meta($user, 'venuscode', true); 
    if (!isset($city) || trim($city)==='') {
        $city = get_user_meta($user, 'city', true);  
    }  
    
    if (!isset($date) || trim($date)==='') {
        $date = current_time('m/d/Y');
    } else {
        $date = date("m/d/Y", strtotime($date));
    }

    $my_product_attributes = array('venus_code' => $venus_code, 'current_date' => $date, 'city' => $city); 
    wcproduct_set_attributes($post_id, $my_product_attributes);

    //get file extention  
    $file_info = wp_get_attachment_url($attach_id);//pathinfo(basename( $files_bits['file']));
    
    $filename = basename ( get_attached_file( $attach_id) );
    
    $file_new_name = generate_file_name().".".wp_check_filetype($file_info)['ext'];
    // Rename the intermediate size 
    rename( $wp_upload_dir['path'] . '/' .$filename,  $wp_upload_dir['path'] . '/' .$file_new_name );
    $downdloadArray =array('name'=> $file_new_name, 'file' => $wp_upload_dir['url'] . '/' . $file_new_name);   
    $file_path = md5($filename);
    $_file_paths[ $file_path ] = $downdloadArray;
    do_action( 'woocommerce_process_product_file_download_paths', $post_id, 0, $downdloadArray );
    
    update_post_meta( $post_id, '_downloadable_files', $_file_paths);
    update_post_meta( $post_id, '_download_limit', '');
    update_post_meta( $post_id, '_download_expiry', '');
    update_post_meta( $post_id, '_download_type', '');
    update_post_meta( $post_id, '_product_image_gallery', '');
     
    return false;    
}
  
function generate_file_name() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);

    for ($i = 0, $result = ''; $i < 16; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }

    return $result;
}
?>
