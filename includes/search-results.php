<div id="search-results-div">
    <div class="searchInner">
    <?php 
    if( isset($_GET['search_text']) ) {
        if( $_GET['selector'] && !empty( $_GET['selector'] ) ){
            $column  = $_GET['selector'];
        }

        if( $_GET['search_text'] && !empty( $_GET['search_text'] ) ){
            $text = $_GET['search_text'];
        }

        $perpage = $a['perpage'];

        if( $column && $text) {

            $paged = ( get_query_var( 'pg' ) ) ? absint( get_query_var( 'pg' ) ) : 1;
            $args = array(
                'posts_per_page'    => $perpage,    
                'post_type'         => 'properties',
                'post_status'       => 'publish',
                'meta_query'        => array(
                                        array(
                                             'key' => $column, 
                                             'value' => $text,
                                             'compare' => 'LIKE'
                                         )
                                    ),
                'paged'             => $paged,
            );

            $query = new WP_Query($args); 
            ?>
            
            <h4>Search results for: <span class="highlighted-color"><?php echo $text ?></span></h4>
            <?php if ($query->found_posts) { ?>
            <div class="found"><strong><?php echo $query->found_posts ?></strong> results found.</div>
            <?php } ?>

            <?php
            if ( $query->have_posts() ) { ?>
            <div class="search-result-wrapper">
                <table id="cma_search" class="table search-result-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $keyword = $text;
                        while ( $query->have_posts() ) : $query->the_post(); 
                            $title = get_the_title();
                            $address = get_field('address');
                            $s_title = ($title) ? preg_replace('#'. preg_quote($keyword) .'#i', '<span class="hlw">\\0</span>', $title) : '';
                            $s_address = ($address) ? preg_replace('#'. preg_quote($keyword) .'#i', '<span class="hlw">\\0</span>', $address) : '';
                            ?>
                            <tr>
                                <td class="search_title"><a href="<?php the_permalink(); ?>"><?php echo $s_title; ?></a></td>
                                <td class="search_address"><?php echo $s_address; ?></td>
                            </tr>
                        <?php endwhile; wp_reset_query(); ?>
                    </tbody>
                </table>
            </div>
            <?php
            /* WP PAGINATION */
            $total_pages = $query->max_num_pages;
            if ($total_pages > 1){ ?>
                <div id="pagination" class="pagination pagination-search" data-pageurl="<?php echo get_permalink(); ?>">
                    <?php
                        wp_cma_pagination($query,$paged);
                    ?>
                </div>
                <?php
            }
            ?>
            <?php
            } else {  ?>
                <p><strong>Nothing matched your search terms.</strong></p>
            <?php
            }
        }
    }
    ?>
    </div>
</div>

