<?php 
$jsonFilePath = plugins_url('cma-import-data'). '/acf.json';
if ( isset($_GET['downloadjson']) && $_GET['downloadjson'] ) { forceDownLoad($filePath); } ?>
<div class="wrap">
    <h1>Import Data</h1>

    <?php if ($message) { 
        $error_type = ($post_ids) ? 'updated':'error'; ?>
        <div id="message" class="<?php echo $error_type; ?> settings-error notice is-dismissible"><p><strong><?php echo $message; ?></strong></p> <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
    <?php } ?>


    <p>
        Use the shortcode <code>[cma-search-form perpage='20' action='']</code> to display the search form.<br>
        'perpage' = Number of items to display per page.<br>
        'action' = Results page' slug.
    </p>
    <p><a href="<?php echo $jsonFilePath; ?>" download target="_blank">Download this json file</a> and import it to ACF plugin.</p>
    <p style="margin:0 0 0"><strong>Custom Field Names:</strong></p>
    

    <?php $customFields = get_property_fieldnames(); ?>
    <ul id="acffields">
        <?php foreach ($customFields as $field => $label) { ?>
            <li data-label="<?php echo $label ?>"><?php echo $field ?></li>
        <?php } ?>
    </ul>

    <hr>
    
    <?php 
    /* IMPORT FORM */ 
    $type = ( isset($action) && $action ) ? $action : 'add';
    $typeOptions = array('add'=>'Add Entries','update'=>'Update Entries');
    ?>
    <form id="importPropertiesForm" action="" method="post" enctype="multipart/form-data" style="margin:0 0 10px"> 
        <input type="hidden" name="_wp_http_referer" value="<?php echo get_admin_url(); ?>admin.php?page=cma-search-data&message=1">
        <div class="form-input-group">
            <input type="hidden" name="admin_url" id="admin_url" value="<?php echo admin_url('admin-ajax.php'); ?>">
        </div>
        <div class="form-input-group importType">
            <label><strong>Select import type:</strong></label><br>
            <?php foreach ($typeOptions as $optField => $optLabel) {
                $is_checked = ($optField==$type) ? 'checked':'';  ?>
                <label class="itype">
                    <input type="radio" name="importtype" value="<?php echo $optField ?>" <?php echo $is_checked ?>>
                    <?php echo $optLabel ?>
                </label>
            <?php } ?>
        </div>
        <div class="form-input-group">
            <label for="average_sale" class="text-shadow text-white">Upload File (CSV):</label>
            <input type="file" class="form-input form-editable" name="cma_data_properties" id="cma_data_properties" >
        </div>
        <div class="form-input-group submitfield">
            <button type="submit" class="button button-primary" name="cma_search_submit_values">Import Data</button> 
        </div>    
    </form>
    
    <?php /* RESULTS */ ?>
    <?php if ($post_ids) { 
        $total = count($post_ids);
        if($import_type=='add') {
            $itemsMsg = ($total > 1) ? 'Imported: ' . $total . ' items': 'Imported: ' . $total . ' item';
        } else {
            $itemsMsg = ($total > 1) ? 'Updated: ' . $total . ' items': 'Updated: ' . $total . ' item'; 
        }
        ?>
        <div id="cma_search_table">
            <div class="count-info"><strong><?php echo $itemsMsg; ?></strong></div>
            <table class="wp-list-table widefat fixed striped cma-search">
                <thead>
                    <tr>
                        <?php foreach ($customFields as $field => $label) { ?>
                            <th class="manage-column column-columnname" id="<?php echo $field ?>" scope="col"><?php echo $label ?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i=0; foreach ($post_ids as $postId) { 
                        $row_value = '';
                        $coordinates = array('latitude','longitude');
                    ?>  
                    <tr>
                        <?php foreach ($customFields as $field => $label) { 
                            if( in_array($field, $coordinates) ) {
                                $field = 'gmapLatLong_'.$field;  
                            } ?>
                            <td><?php echo get_field( $field, $postId ); ?></td>
                        <?php } ?>
                    </tr>
                    <?php $i++; } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

</div>