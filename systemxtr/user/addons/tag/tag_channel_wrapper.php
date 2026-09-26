<?php
require_once PATH_MOD . 'channel/mod.channel.php';
class Tag_channel_wrapper extends Channel {
    public $paginate;
    public $total_pages;
    public $current_page;
    public $offset;
    public $page_next;
    public $page_previous;
    public $page_links;
    public $total_rows;
    public $per_page;
    public $pagination_links;
    public $p_page;
    public $p_limit;
}

?>