<?php
function badges_get_tags()
{
    return get_terms( array( 'taxonomy' => 'product_tag', 'parent' => 0, 'hide_empty' => false, 'fields' => 'id=>name' ) );
}

function badges_get_categorys()
{
    return get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'fields' => 'id=>name' ) );
}