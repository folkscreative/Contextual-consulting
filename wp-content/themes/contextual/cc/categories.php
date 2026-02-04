<?php
/**
 * category related things
 * used by posts and knowledge hub items
 */


add_action( 'category_add_form_fields', 'cc_categories_add_fields' );
function cc_categories_add_fields() {
    $image_id = null;
    ?>

    <div id="category_custom_image"></div>
    <input 
          type="hidden" 
          id="category_custom_image_url"     
          name="category_custom_image_url">
    <div style="margin-bottom: 20px;">
        <span>Category Image </span>
        <a href="#" 
            class="button custom-button-upload" 
            id="custom-button-upload">Upload image</a>
        <a href="#" 
            class="button custom-button-remove" 
            id="custom-button-remove" 
            style="display: none">Remove image</a>
    </div>

	<?php
}

add_action ( 'category_edit_form_fields', 'cc_categories_edit_fields', 10, 2 );
function cc_categories_edit_fields($ttObj, $taxonomy) {

    $term_id = $ttObj->term_id;
    $image = '';
    $image = get_term_meta( $term_id, 'term_image', true );

    ?>
    <tr class="form-field term-image-wrap">
      <th scope="row"><label for="image">Image</label></th>
	<td>
        <?php if ( $image ): ?>
        <span id="category_custom_image">
           <img src="<?php echo $image; ?>" style="width: 100%"/>
        </span>
        <input 
           type="hidden" 
           id="category_custom_image_url" 
           name="category_custom_image_url">
                
        <span>
           <a href="#" 
             class="button custom-button-upload" 
             id="custom-button-upload" 
             style="display: none">Upload image</a>
           <a href="#" class="button custom-button-remove">Remove image</a>                    
        </span>
        <?php else: ?>
        <span id="category_custom_image"></span>
        <input 
            type="hidden" 
            id="category_custom_image_url" 
            name="category_custom_image_url">
        <span>
           <a href="#" 
              class="button custom-button-upload" 
              id="custom-button-upload">Upload image</a>
           <a href="#" 
              class="button custom-button-remove" 
              style="display: none">Remove image</a>
        </span>
        <?php endif; ?>
        </td>
    </tr>
        
    <?php
}

add_action( 'create_term', 'cc_categories_save_image' );
add_action( 'edit_term', 'cc_categories_save_image' );
function cc_categories_save_image( $term_id ){
    if( isset( $_POST['category_custom_image_url'] ) ){
        update_term_meta( 
            $term_id, 
            'term_image', 
            esc_url( $_POST['category_custom_image_url'] ) );
    }
}

/*
add_action( 'create_term', 'cc_categories_create_term' );
function cc_categories_create_term($term_id) {
    // add term meta data
    update_term_meta( 
        $term_id, 
        'term_image',   
        esc_url($_REQUEST['category_custom_image_url'])
    );

}

add_action( 'edit_term', 'cc_categories_edit_term' );
function cc_categories_edit_term($term_id) {
    $image = '';
    $image = get_term_meta( $term_id, 'term_image', true);

    if ( $image )
    update_term_meta( 
        $term_id, 
        'term_image', 
        esc_url( $_POST['category_custom_image_url']) );

    else
    add_term_meta( 
        $term_id, 
        'term_image', 
        esc_url( $_POST['category_custom_image_url']) );

}
*/
