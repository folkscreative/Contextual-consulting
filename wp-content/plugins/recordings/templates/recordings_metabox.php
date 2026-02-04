<table> 
    <tr valign="top">
        <th class="metabox_label_column">
            <label for="registration_link">Start Date</label>
        </th>
        <td>
            <input type="text" id="registration_link" name="registration_link" value="<?php echo @get_post_meta($post->ID, 'registration_link', true); ?>" />
        </td>
    </tr>
    <tr valign="top">
        <th class="metabox_label_column">
            <label for="meta_b">Registration Link</label>
        </th>
        <td>
            <input type="text" id="meta_b" name="meta_b" value="<?php echo @get_post_meta($post->ID, 'meta_b', true); ?>" />
        </td>
    </tr>
    <tr valign="top">
        <th class="metabox_label_column">
            <label for="meta_c">Venue Link</label>
        </th>
        <td>
            <input type="text" id="meta_c" name="meta_c" value="<?php echo @get_post_meta($post->ID, 'meta_c', true); ?>" />
        </td>
    </tr>                
</table>
