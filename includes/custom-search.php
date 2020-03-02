<?php
    $selector       = ( !empty($_GET['selector']) ) ? esc_attr__( $_GET['selector'] ) : '';
    $search_text    = ( !empty($_GET['search_text']) ) ? esc_attr__( $_GET['search_text'] ) : '';
    $actionURL = ( isset($a['action']) && $a['action'] ) ? get_site_url() . '/' . $a['action'] : '';
?>
<div class="custom-search-container">
    <form action="<?php echo $actionURL ?>" method="get">
        <div class="form-group-search searchby">
            <label for="custom-search-selector">Search by:</label>
            <select name="selector" class="search-selector" id="custom-search-selector" >
                <option value="address" <?php echo ($selector == 'address') ? ' selected ' : ''; ?>>Location</option>
                <option value="community_name" <?php echo ($selector == 'community_name') ? ' selected ' : ''; ?>>Name</option>
            </select>
        </div>
        <div class="form-group-search inputfield">
            <input type="text" name="search_text" class="search-field" placeholder="zip, address, or city" value="<?php echo $search_text; ?>">
        </div>
        <div class="form-group-search submitbtn">
            <button type="submit" class="srchBtn">Search</button>
        </div>
    </form>
</div>