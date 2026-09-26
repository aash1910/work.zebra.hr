<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Fast Entries Plugin
 *
 * @package     ExpressionEngine
 * @category    Plugin
 * @author      Your Name
 * @link        https://yourwebsite.com
 */

class Fast_entries
{
    public $return_data = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->load->database();
        $this->return_data = $this->entries();
    }

    /**
     * Main entries method - replaces {exp:channel:entries}
     *
     * @return string
     */
    public function entries()
    {
        // Get parameters
        $channel = ee()->TMPL->fetch_param('channel');
        $status = ee()->TMPL->fetch_param('status', 'open');
        $limit = (int) ee()->TMPL->fetch_param('limit', 16);
        $orderby = ee()->TMPL->fetch_param('orderby', 'date');
        $sort = ee()->TMPL->fetch_param('sort', 'desc');
        $paginate = ee()->TMPL->fetch_param('paginate', 'yes');
        $cache_count = ee()->TMPL->fetch_param('cache_count', 'no'); // Optional caching

        // Auto-detect category from segment 3
        $category_url_title = ee()->uri->segment(3);

        if (!$channel) {
            return $this->no_results();
        }

        // Parse orderby and sort (supports pipe-delimited)
        $orderby_array = explode('|', $orderby);
        $sort_array = explode('|', $sort);

        // Get current page from URI (EE-style: P0, P16, P32, etc.)
        $offset = 0;
        $current_page = 1;
        $last_segment = ee()->uri->segment(ee()->uri->total_segments());
        
        if ($last_segment && strpos($last_segment, 'P') === 0) {
            $offset = (int) substr($last_segment, 1);
            $current_page = ($offset / $limit) + 1;
        }

        // Get total count (with optional caching)
        if ($cache_count === 'yes') {
            $cache_key = 'fast_entries_count_' . md5($channel . $status . $category_url_title);
            $total_rows = ee()->cache->get($cache_key);
            
            if ($total_rows === false) {
                $total_rows = $this->get_total_count($channel, $status, $category_url_title);
                // Cache for 5 minutes (300 seconds)
                ee()->cache->save($cache_key, $total_rows, 300);
            }
        } else {
            $total_rows = $this->get_total_count($channel, $status, $category_url_title);
        }

        // Build main query
        ee()->db->select('ct.entry_id');
        ee()->db->distinct();
        ee()->db->from('exp_channel_titles ct');

        // Channel filter
        if (strpos($channel, '|') !== false) {
            ee()->db->join('exp_channels ch', 'ch.channel_id = ct.channel_id', 'inner');
            ee()->db->where_in('ch.channel_name', explode('|', $channel));
        } else {
            ee()->db->join('exp_channels ch', 'ch.channel_id = ct.channel_id', 'inner');
            ee()->db->where('ch.channel_name', $channel);
        }

        // Status filter
        if ($status !== 'all') {
            if (strpos($status, '|') !== false) {
                ee()->db->where_in('ct.status', explode('|', $status));
            } else {
                ee()->db->where('ct.status', $status);
            }
        }

        // Category filter (from segment 3)
        if ($category_url_title && $category_url_title !== '' && strpos($category_url_title, 'P') !== 0) {
            ee()->db->join('exp_category_posts cp', 'cp.entry_id = ct.entry_id', 'inner');
            ee()->db->join('exp_categories cat', 'cat.cat_id = cp.cat_id', 'inner');
            ee()->db->where('cat.cat_url_title', $category_url_title);
        }

        // Apply ORDER BY
        foreach ($orderby_array as $index => $order_field) {
            $direction = strtoupper($sort_array[$index] ?? 'DESC');
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'DESC';
            }

            // Handle different order types
            if ($order_field === 'date') {
                ee()->db->order_by('ct.entry_date', $direction);
            } elseif ($order_field === 'title') {
                ee()->db->order_by('ct.title', $direction);
            } elseif ($order_field === 'view_count_one') {
                ee()->db->order_by('ct.view_count_one', $direction);
            } elseif ($order_field === 'view_count_two') {
                ee()->db->order_by('ct.view_count_two', $direction);
            } elseif ($order_field === 'view_count_three') {
                ee()->db->order_by('ct.view_count_three', $direction);
            } elseif ($order_field === 'view_count_four') {
                ee()->db->order_by('ct.view_count_four', $direction);
            } elseif (is_numeric($order_field)) {
                // Custom field - join the field table
                $field_id = (int) $order_field;
                $alias = 'cdf_' . $field_id;
                
                ee()->db->join(
                    "exp_channel_data_field_{$field_id} {$alias}",
                    "{$alias}.entry_id = ct.entry_id",
                    'left'
                );
                ee()->db->order_by("{$alias}.field_id_{$field_id}", $direction);
            }
        }

        // Apply limit and offset
        ee()->db->limit($limit, $offset);

        $query = ee()->db->get();

        // Log for debugging
        $this->write_log("Query executed - Total rows: {$total_rows}, Offset: {$offset}, Limit: {$limit}, Results: " . $query->num_rows());

        if ($query->num_rows() == 0) {
            return $this->no_results();
        }

        // Parse template with entry_id
        $tagdata = ee()->TMPL->tagdata;
        $output = '';
        $count = 0;

        foreach ($query->result() as $row) {
            $count++;
            
            $variables = [
                'entry_id' => $row->entry_id,
                'count' => $count,
                'absolute_count' => $offset + $count,
                'total_results' => $total_rows
            ];
            
            $output .= ee()->TMPL->parse_variables_row($tagdata, $variables);
        }

        // Store pagination data in cache for pagination tag
        ee()->session->cache['fast_entries']['pagination'] = [
            'total_rows' => $total_rows,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $current_page,
            'total_pages' => ceil($total_rows / $limit),
            'base_url' => $this->get_base_url()
        ];

        // Auto-append pagination if enabled
        if ($paginate === 'yes' && $total_rows > $limit) {
            $output .= $this->render_pagination();
        }

        return $output;
    }

    /**
     * Get total count of entries matching criteria
     *
     * @param string $channel
     * @param string $status
     * @param string $category_url_title
     * @return int
     */
    private function get_total_count($channel, $status, $category_url_title)
    {
        // Use a more efficient counting approach
        // Instead of COUNT(DISTINCT), use a subquery with GROUP BY
        
        if ($category_url_title && $category_url_title !== '' && strpos($category_url_title, 'P') !== 0) {
            // When filtering by category, use optimized query
            $sql = "SELECT COUNT(*) as total FROM (
                SELECT ct.entry_id
                FROM exp_channel_titles ct
                INNER JOIN exp_channels ch ON ch.channel_id = ct.channel_id
                INNER JOIN exp_category_posts cp ON cp.entry_id = ct.entry_id
                INNER JOIN exp_categories cat ON cat.cat_id = cp.cat_id
                WHERE ";
            
            // Channel filter
            if (strpos($channel, '|') !== false) {
                $channels = explode('|', $channel);
                $escaped_channels = array();
                foreach ($channels as $ch) {
                    $escaped_channels[] = ee()->db->escape($ch);
                }
                $channel_list = implode(',', $escaped_channels);
                $sql .= "ch.channel_name IN ({$channel_list}) AND ";
            } else {
                $sql .= "ch.channel_name = " . ee()->db->escape($channel) . " AND ";
            }
            
            // Status filter
            if ($status !== 'all') {
                if (strpos($status, '|') !== false) {
                    $statuses = explode('|', $status);
                    $escaped_statuses = array();
                    foreach ($statuses as $st) {
                        $escaped_statuses[] = ee()->db->escape($st);
                    }
                    $status_list = implode(',', $escaped_statuses);
                    $sql .= "ct.status IN ({$status_list}) AND ";
                } else {
                    $sql .= "ct.status = " . ee()->db->escape($status) . " AND ";
                }
            }
            
            // Category filter
            $sql .= "cat.cat_url_title = " . ee()->db->escape($category_url_title) . "
                GROUP BY ct.entry_id
            ) as subquery";
            
            $result = ee()->db->query($sql);
            return (int) $result->row()->total;
            
        } else {
            // No category filter - simpler, faster query
            ee()->db->select('COUNT(ct.entry_id) as total');
            ee()->db->from('exp_channel_titles ct');
            
            // Channel filter
            if (strpos($channel, '|') !== false) {
                ee()->db->join('exp_channels ch', 'ch.channel_id = ct.channel_id', 'inner');
                ee()->db->where_in('ch.channel_name', explode('|', $channel));
            } else {
                ee()->db->join('exp_channels ch', 'ch.channel_id = ct.channel_id', 'inner');
                ee()->db->where('ch.channel_name', $channel);
            }
            
            // Status filter
            if ($status !== 'all') {
                if (strpos($status, '|') !== false) {
                    ee()->db->where_in('ct.status', explode('|', $status));
                } else {
                    ee()->db->where('ct.status', $status);
                }
            }
            
            $result = ee()->db->get();
            return (int) $result->row()->total;
        }
    }

    /**
     * Standalone pagination tag
     * Usage: {exp:fast_entries:pagination}
     *
     * @return string
     */
    public function pagination()
    {
        return $this->render_pagination();
    }

    /**
     * Standalone total results tag
     * Usage: {exp:fast_entries:total_results}
     *
     * @return string
     */
    public function total_results()
    {
        $data = ee()->session->cache['fast_entries']['pagination'] ?? null;
        
        if (!$data) {
            return '0';
        }
        
        return (string) $data['total_rows'];
    }

    /**
     * Render pagination HTML
     *
     * @return string
     */
    private function render_pagination()
    {
        $data = ee()->session->cache['fast_entries']['pagination'] ?? null;

        if (!$data || $data['total_pages'] <= 1) {
            return '';
        }

        $total_rows = $data['total_rows'];
        $limit = $data['limit'];
        $current_page = $data['current_page'];
        $total_pages = $data['total_pages'];
        $base_url = $data['base_url'];

        $pagination = '<ul class="pagination">';

        // Previous link
        if ($current_page > 1) {
            $prev_offset = ($current_page - 2) * $limit;
            $prev_url = $prev_offset > 0 ? $base_url . '/P' . $prev_offset : $base_url;
            $pagination .= '<li class="page-item"><a href="/' . $prev_url . '" class="page-link"><i class="fas fa-angle-left"></i></a></li>';
        } else {
            $pagination .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-angle-left"></i></span></li>';
        }

        // Page numbers (with ellipsis for many pages)
        $range = 5; // Show 5 pages at a time
        $start_page = max(1, $current_page - floor($range / 2));
        $end_page = min($total_pages, $start_page + $range - 1);

        // Adjust start if we're near the end
        if ($end_page - $start_page < $range - 1) {
            $start_page = max(1, $end_page - $range + 1);
        }

        // First page
        if ($start_page > 1) {
            $pagination .= '<li class="page-item"><a href="/' . $base_url . '" class="page-link">1</a></li>';
            if ($start_page > 2) {
                $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Page range
        for ($i = $start_page; $i <= $end_page; $i++) {
            $page_offset = ($i - 1) * $limit;
            $page_url = $page_offset > 0 ? $base_url . '/P' . $page_offset : $base_url;

            if ($i == $current_page) {
                $pagination .= '<li class="page-item active"><a href="/' . $page_url . '" class="page-link">' . $i . '</a></li>';
            } else {
                $pagination .= '<li class="page-item"><a href="/' . $page_url . '" class="page-link">' . $i . '</a></li>';
            }
        }

        // Last page
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $last_offset = ($total_pages - 1) * $limit;
            $pagination .= '<li class="page-item"><a href="/' . $base_url . '/P' . $last_offset . '" class="page-link">' . $total_pages . '</a></li>';
        }

        // Next link
        if ($current_page < $total_pages) {
            $next_offset = $current_page * $limit;
            $next_url = $base_url . '/P' . $next_offset;
            $pagination .= '<li class="page-item"><a href="/' . $next_url . '" class="page-link"><i class="fas fa-angle-right"></i></a></li>';
        } else {
            $pagination .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-angle-right"></i></span></li>';
        }

        $pagination .= '</ul>';

        return $pagination;
    }

    /**
     * Get base URL without pagination segment
     *
     * @return string
     */
    private function get_base_url()
    {
        $uri_string = ee()->uri->uri_string();
        // Remove /P### from end if exists
        return rtrim(preg_replace('/\/P\d+$/', '', $uri_string), '/');
    }

    /**
     * Handle no results
     *
     * @return string
     */
    private function no_results()
    {
        $tagdata = ee()->TMPL->tagdata;

        if (preg_match("/{if no_results}(.*?){\/if}/s", $tagdata, $match)) {
            return $match[1];
        }

        return '';
    }

    /**
     * Write to log file
     *
     * @param string $message
     * @return void
     */
    private function write_log($message)
    {
        $log_path = PATH_THIRD . 'fast_entries/logs/fast_entries.log';

        // Create logs directory if it doesn't exist
        $log_dir = dirname($log_path);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        // Prepare log entry with timestamp
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}" . PHP_EOL;

        // Write to log file
        file_put_contents($log_path, $log_entry, FILE_APPEND);
    }
}

// End of file pi.fast_entries.php
