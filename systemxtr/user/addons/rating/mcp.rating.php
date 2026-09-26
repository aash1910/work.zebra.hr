<?php

use EllisLab\ExpressionEngine\Library\CP;
use EEHarbor\Rating\FluxCapacitor\Base\Mcp;
use EllisLab\ExpressionEngine\Library\CP\Table;

class Rating_mcp extends Mcp
{
    use \EEHarbor\Rating\Library\AddonBuilderTrait;

    private $field_limit        = 25;
    private $row_limit          = 50;
    private $locked_fields      = array('review', 'rating');
    private $locked_templates   = array('default_template');

    private $csv_separator      = ","; // Alternative: "\t"

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access  public
     * @param   bool        Enable calling of methods based on URI string
     * @return  string
     */

    public function __construct()
    {
        parent::__construct();

        // instantiate the addonBuilder "construct"
        $this->addonBuilderConstruct('module');

        $this->cached_vars['lang_module_version']   = lang('rating_module_version');
        $this->cached_vars['module_version']        = ee('App')->get('rating')->getVersion();
        $this->cached_vars['module_menu_highlight'] = 'rated_entries';

        ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . URL_THIRD_THEMES . 'rating/css/solspace-fa.css">');
    }

    // END Rating_cp_base()

    // --------------------------------------------------------------------

    /**
     * index()
     *
     * @access  public
     * @param   string
     * @return  string
     */

    public function index()
    {
        return $this->entries();
    }

    // END index()

    // --------------------------------------------------------------------

    /**
     * ratings_delete
     *
     * @access  public
     * @param   array   $post
     * @return  string  redirect message
     */

    public function ratings_delete()
    {
        //---------------------------------------------
        //  Fetch ratings
        //---------------------------------------------

        $ratings    = $this->fetch('Rating', $_POST['selections'])
            ->all();

        foreach ($ratings as $rating) {
            $channels[] = $rating->channel_id;
            $entries[]  = $rating->entry_id;
            $members[]  = $rating->rating_author_id;
            $ids[]      = $rating->rating_id;
        }

        //---------------------------------------------
        //  Make the model do the work
        //---------------------------------------------

        $this->fetch('Rating', $_POST['selections'])
            ->delete();

        // --------------------------------------------
        //  Update Member's Statistics
        // --------------------------------------------

        $this->make('Stat')
            ->update_member_stats($members);

        // --------------------------------------------
        //  Update Channel Statistics
        // --------------------------------------------

        $this->make('Stat')
            ->update_channel_stats($channels);

        // --------------------------------------------
        //  Update rating stats
        // --------------------------------------------

        $this->make('Stat')
            ->update_entry_stats($entries);

        //---------------------------------------------
        //  Redirect
        //---------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'ratings',
            'msg'       => 'ratings_deleted'
        )));
    }

    //  End ratings_delete()

    // --------------------------------------------------------------------

    /**
     * rating_form()
     *
     * @access  public
     * @return  string
     */

    public function rating_form($message = '')
    {
        $this->prep_message($message, true, true);

        if (! ee()->input->get('rating_id')) {
            return false;
        }

        // --------------------------------------------
        //  Current Values
        // --------------------------------------------

        $prefModel      = $this->make('Rating');
        $defaultPrefs   = $prefModel->default_prefs;

        //  Until we know how to use EE 3's models to grab from variable column tables we go with active record
        $prefs  = ee()->db->where(array('rating_id' => ee()->input->get('rating_id')))->get('ratings')->row_array();

        $entry  = $this->fetch('Rating', ee()->input->get('rating_id'))
            ->first();

        $entry_title    = $entry->ChannelEntry->title;

        // --------------------------------------------
        //  Merge custom fields on to array
        // --------------------------------------------

        $fields = $this->fetch('Field')
            ->order('field_order')
            ->all();

        foreach ($fields as $field) {
            $defaultPrefs[$field->field_name]   = array(
                'type'          => ($field->field_type == 'number') ? 'select' : $field->field_type,
                'field_label'   => $field->field_label,
                'default'       => '',
                'choices'       => array(
                    ''  => '',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5'
                )
            );
        }

        // --------------------------------------------
        //  Start sections
        // --------------------------------------------

        $sections = array();
        $main_section = array();

        foreach ($defaultPrefs as $short_name => $data) {
            $desc_name  = $short_name . '_subtext';
            $desc       = lang($desc_name);

            //if we don't have a description don't set it
            $desc       = ($desc !== $desc_name) ? $desc : '';

            $required   = false;

            //  Populate status with custom statuses
            if ($short_name == 'status') {
                $statuses   = $this->fetch('Rating')
                    ->filter('status', 'NOT IN', array('open', 'closed', 'reported'))
                    ->all()
                    ->getDictionary('status', 'status');

                foreach ($statuses as $status) {
                    $data['choices'][$status]   = ucwords($status);
                }
            }

            $fields     = array(
                $short_name => array_merge($data, array(
                    'value'     => isset($prefs[$short_name]) ?
                                    $prefs[$short_name] :
                                    $data['default'],
                    //we just require everything
                    //its a settings form
                    'required'  => $required
                ))
            );

            // --------------------------------------------
            //  Set the row now
            // --------------------------------------------

            $main_section[$short_name] = array(
                'hide'      => ($data['type'] == 'hidden') ? true : false,
                'title'     => (! empty($data['field_label'])) ? lang($data['field_label']) : lang($short_name),
                'desc'      => $desc,
                'fields'    => $fields
            );
        }

        $sections[] = $main_section;

        $this->cached_vars['sections'] = $sections;

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => 'rating_update'
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'rating_update'
            )),
            'cp_page_title'         => str_replace('%entry_title%', $entry_title, lang('rating_update')),
            'save_btn_text'         => 'btn_save_rating',
            'save_btn_text_working' => 'btn_saving_rating'
        );

        return $this->mcp_view(array(
            'file'      => 'form',
            'highlight' => 'ratings',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(
                    lang('ratings'),
                    $this->mcp_link(array('method' => 'ratings'), false)
                ),
                array(str_replace('%entry_title%', $entry_title, lang('rating_update')))
            )
        ));
    }

    //  End rating_form()

    // --------------------------------------------------------------------

    /**
     * rating_unquarantine()
     *
     * @access  public
     * @param   array   $post
     * @return  string  redirect message
     */

    public function rating_unquarantine($rating_id = '')
    {
        $id = (! empty($rating_id)) ? $rating_id : ee()->input->get('rating_id');

        //---------------------------------------------
        //  Make the model do the work
        //---------------------------------------------

        $rating = $this->fetch('Rating', $id)
            ->first();

        $rating->quarantine = 'n';
        $rating->save();

        $quarantines    = $this->fetch('Quarantine')
            ->filter('rating_id', $id)
            ->all();

        foreach ($quarantines as $quarantine) {
            $quarantine->status = 'closed';
            $quarantine->save();
        }

        $channels[] = $rating->channel_id;
        $entries[]  = $rating->entry_id;
        $members[]  = $rating->rating_author_id;
        $ids[]      = $rating->rating_id;

        // --------------------------------------------
        //  Update Member's Statistics
        // --------------------------------------------

        $this->make('Stat')
            ->update_member_stats($members);

        // --------------------------------------------
        //  Update Channel Statistics
        // --------------------------------------------

        $this->make('Stat')
            ->update_channel_stats($channels);

        // --------------------------------------------
        //  Update rating stats
        // --------------------------------------------

        $this->make('Stat')
            ->update_entry_stats($entries);

        // --------------------------------------------
        //  Return
        // --------------------------------------------

        if (! empty($rating_id)) {
            return true;
        }

        //---------------------------------------------
        //  Redirect
        //---------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'ratings',
            'msg'       => 'rating_unquarantined'
        )));
    }

    //  End rating_unquarantine()

    // --------------------------------------------------------------------

    /**
     * rating_update()
     *
     * @access  public
     * @return  bool
     */

    public function rating_update()
    {
        $prefModel      = $this->make('Rating');
        $defaultPrefs   = $prefModel->default_prefs;

        // --------------------------------------------
        //  validate (custom method)
        // --------------------------------------------

        $result = $prefModel->validateDefaultPrefs($_POST);

        if (! $result->isValid()) {
            $errors = array();

            foreach ($result->getAllErrors() as $name => $error_list) {
                foreach ($error_list as $error_name => $error_msg) {
                    $errors[] = lang($name) . ': ' . $error_msg;
                }
            }

            return $this->show_error($errors);
        }

        // --------------------------------------------
        //  Update
        // --------------------------------------------

        //  Until we know how to use EE 3's models to grab from variable column tables we go with active record
        $prefs  = ee()->db->where(array('rating_id' => ee()->input->get_post('rating_id')))->get('ratings')->row_array();

        // --------------------------------------------
        //  Capture quarantine state
        // --------------------------------------------

        $quarantined    = ($prefs['quarantine'] == 'y') ? true : false;

        // --------------------------------------------
        //  Merge
        // --------------------------------------------

        $prefs  = array_merge($prefs, $_POST);

        // --------------------------------------------
        //  Get fields so that we can set defaults
        // --------------------------------------------

        $fields = $this->fetch('Field')
            ->all();

        foreach ($fields as $field) {
            if ($field->field_type != 'number') {
                continue;
            }

            $prefs[$field->field_name]  = (empty($prefs[$field->field_name])) ? 0 : $prefs[$field->field_name];
        }

        unset($prefs['rating_id']);

        ee()->db->update('ratings', $prefs, array('rating_id' => ee()->input->get_post('rating_id')));

        // --------------------------------------------
        //  Quarantined and unquarantined?
        // --------------------------------------------

        if ($quarantined and $prefs['quarantine'] == 'n') {
            $this->rating_unquarantine(ee()->input->get_post('rating_id'));
        }

        // --------------------------------------------
        //  Return view
        // --------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'rating_form',
            'rating_id' => ee()->input->get_post('rating_id'),
            'msg'       => 'rating_updated'
        )));
    }

    //  End rating_update()

    // --------------------------------------------------------------------

    /**
     * rating_comments()
     *
     * @access  public
     * @return  string
     */

    public function rating_comments()
    {
        $this->prep_message();

        //  ----------------------------------------
        //  Query
        //  ----------------------------------------

        $reviews = $this->fetch('Review')
            ->filter('rating_id', ee()->input->get('rating_id'))
            ->filter('rating_review', '!=', '');

        //  ----------------------------------------
        //  Start table
        //  ----------------------------------------

        $tableData = array();

        //  ----------------------------------------
        //  Anything?
        //  ----------------------------------------

        if ($reviews->count() > 0) {
            //  ----------------------------------------
            //  Pagination
            //  ----------------------------------------

            $page   = 0;

            if ($reviews->count() > $this->row_limit) {
                $page   = $this->get_post_or_zero('page') ?: 1;

                $mcp_link_array = array(
                    'method' => __FUNCTION__
                );

                $this->cached_vars['pagination'] = ee('CP/Pagination', $reviews->count())
                                    ->displayPageLinks(10)
                                    ->perPage($this->row_limit)
                                    ->currentPage($page)
                                    ->render($this->mcp_link($mcp_link_array, false));

                $reviews->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
            }

            foreach ($reviews->all() as $review) {
                $tableData[] = array(
                    $review->review_id,
                    array(
                        'content'   => $review->name,
                        'href'      => $this->mcp_link(array(
                            'method'    => 'rating_comment_form',
                            'review_id' => $review->review_id
                        ))
                    ),
                    $review->rating_review,
                    $this->human_time($review->review_date),
                    array(
                        'name'      => 'selections[]',
                        'value'     => $review->review_id,
                        'data'      => array(
                            'confirm' => lang('comment') . ': <b>' . htmlentities(lang('Comment by') . ' ' . $review->name . ' on ' . $this->human_time($review->review_date), ENT_QUOTES) . '</b>'
                        )
                    )
                );
            }
        }

        // -------------------------------------
        //  Build table
        // -------------------------------------

        $table = ee('CP/Table', array(
            'sortable'  => false,
            'search'    => false,
        ));

        $table->setColumns(
            array(
                'id' => array(
                    'type'          => Table::COL_ID
                ),
                'name'      => '',
                'comment'   => '',
                'date'  => '',
                array(
                    'type'          => Table::COL_CHECKBOX,
                    'name'          => 'selection'
                )
            )
        );

        $table->setData($tableData);

        $table->setNoResultsText('no_rating_comments');

        $this->cached_vars['table'] = $table->viewData(
            $this->mcp_link(array('method' => __FUNCTION__), false)
        );

        // -------------------------------------
        //  Modal for delete confirmation
        // -------------------------------------

        $this->cached_vars['footer'] = array(
            'type'          => 'bulk_action_form',
            'submit_lang'   => lang('submit')
        );

        $this->mcp_modal_confirm(array(
            'form_url'  => $this->mcp_link(array('method' => 'rating_comments_delete')),
            'name'      => 'comments',
            'kind'      => lang('comments'),
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'rating_comments'
            )),
            'cp_page_title'         => lang('comments')
        );

        return $this->mcp_view(array(
            'file'      => 'list',
            'highlight' => 'ratings',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(
                    lang('ratings'),
                    $this->mcp_link(array('method' => 'ratings'), false)
                ),
                array(lang('comments'))
            )
        ));
    }

    // END rating_comments()

    // --------------------------------------------------------------------

    /**
     * entries()
     *
     * @access  public
     * @return  string
     */

    public function entries()
    {
        $this->prep_message();

        //  ----------------------------------------
        //  Begin base url and add as we go
        //  ----------------------------------------

        $base_url = $this->mcp_link(array(
            'method' => __FUNCTION__
        ), false);

        //  ----------------------------------------
        //  Prepare filters
        //  ----------------------------------------

        // $collections = $this->fetch('Rating')
        //     ->fields('collection')
        //     ->all()
        //     ->getDictionary('collection', 'collection');

        $collection_query = ee()->db->select('collection')->group_by('collection')->get('ratings');

        $collections = array();

        foreach ($collection_query->result() as $collection_results) {
            $collections[$collection_results->collection] = $collection_results->collection;
        }

        $collections = ee('CP/Filter')->make('filter_by_collection', 'filter_by_collection', $collections);

        $channels   = ee('Model')
            ->get('Channel')
            ->all()
            ->getDictionary('channel_id', 'channel_title');
        $channels = ee('CP/Filter')->make('filter_by_channel', 'filter_by_channel', $channels);
        $channels->setPlaceholder('search');

        // -------------------------------------
        //  Render filters
        // -------------------------------------

        $filters = ee('CP/Filter')
            ->add($collections)
            ->add($channels);

        $this->cached_vars['filters']   = $filters->render($base_url);

        // -------------------------------------
        //  Add filters to base url
        // -------------------------------------

        $filter_values = $filters->values();
        $base_url->addQueryStringVariables($filter_values);

        //  ----------------------------------------
        //  Query
        //  ----------------------------------------

        $stats = $this->fetch('Stat')
            ->with('ChannelEntry')
            ->filter('entry_id', '!=', 0);

        //  ----------------------------------------
        //  Entry id
        //  ----------------------------------------

        if (ee()->input->get_post('entry_id')) {
            $base_url->addQueryStringVariables(array('entry_id' => ee()->input->get_post('entry_id')));

            $stats->filter('entry_id', ee()->input->get_post('entry_id'));

            $title  = ee('Model')
                ->get('ChannelEntry', ee()->input->get_post('entry_id'))
                ->first()
                ->title;
        }

        // -------------------------------------
        //  Filter by collection
        // -------------------------------------

        if ($collections->value()) {
            $stats->filter('collection', $collections->value());

            $collection = $this->fetch('Stat')
                ->filter('collection', $collections->value())
                ->first()
                ->collection;
        } else {
            $stats->filter('collection', 'all');
        }

        // -------------------------------------
        //  Filter by channel
        // -------------------------------------

        if ($channels->value()) {
            $stats->filter('channel_id', $channels->value());
        }

        // -------------------------------------
        //  Column sorting
        // -------------------------------------

        $col_map = array(
            'date'          => 'rating_date',
            'edit_entry'    => 'edit_entry',
        );

        if (isset($col_map[ee()->input->get_post('sort_col')])) {
            $sort = (ee()->input->get_post('sort_dir') == 'asc') ? 'ASC' : 'DESC';

            $col = $col_map[ee()->input->get_post('sort_col')];

            if ($col == 'edit_entry') {
                $col    = 'ChannelEntry.title';
            }

            $stats->order($col, $sort);
        } else {
            $stats->order('ChannelEntry.title', 'asc');
        }

        //  ----------------------------------------
        //  Start table
        //  ----------------------------------------

        $tableData = array();

        //  ----------------------------------------
        //  Anything?
        //  ----------------------------------------

        if ($stats->count() > 0) {
            //  ----------------------------------------
            //  Pagination
            //  ----------------------------------------

            $page   = 0;

            if ($stats->count() > $this->row_limit) {
                $page   = $this->get_post_or_zero('page') ?: 1;

                $this->cached_vars['pagination'] = ee('CP/Pagination', $stats->count())
                                    ->displayPageLinks(10)
                                    ->perPage($this->row_limit)
                                    ->currentPage($page)
                                    ->render($base_url);

                $stats->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
            }

            foreach ($stats->all() as $stat) {
                $tableData[] = array(
                    array(
                        'content'   => $stat->ChannelEntry->title,
                        'href'      => ee('CP/URL')->make('publish/edit/entry/' . $stat->entry_id)
                    ),
                    array(
                        'content'   => lang('view_ratings'),
                        'href'      => ($stat->entry_id == 0) ? null : $this->mcp_link(array(
                            'method'    => 'ratings',
                            'entry_id'  => $stat->entry_id,
                            'filter_by_collection'  => ($collections->value()) ? $collections->value() : ''
                        ))
                    ),
                    array(
                        'content'   => $this->fetch('Rating')->filter('entry_id', $stat->entry_id)->count(),
                    ),
                );
            }
        }

        // -------------------------------------
        //  Build table
        // -------------------------------------

        $table = ee('CP/Table', array(
            'sortable'  => true,
            'search'    => false,
            'sort_col'  => 'rating_date',
            'sort_dir'  => 'desc'
        ));

        $cols   = array(
            'edit_entry'    => '',
            'view_ratings'  => '',
            'count'         => array(
                'type'  => 'text'
            )
        );

        $table->setColumns($cols);

        $table->setData($tableData);

        $table->setNoResultsText('no_ratings');

        $this->cached_vars['table'] = $table->viewData($base_url);

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        $page_title = lang('ratings');

        if (isset($title)) {
            $page_title = lang('ratings_for_entry') . ': ' . $title;
        } elseif (isset($collection)) {
            $page_title = lang('ratings_for_collection') . ': ' . $collection;
        }

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'ratings'
            )),
            'cp_page_title'         => $page_title
        );

        $crumbs = array(
            array(lang('ratings'))
        );

        if (isset($title)) {
            $crumbs = array(
                array(
                    lang('ratings'),
                    $this->mcp_link(array('method' => 'ratings'), false)
                ),
                array($title)
            );
        }

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => __FUNCTION__
        ));

        return $this->mcp_view(array(
            'file'      => 'list',
            'highlight' => 'rated_entries',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => $crumbs
        ));
    }

    // End entries()

    // --------------------------------------------------------------------

    /**
     * ratings()
     *
     * @access  public
     * @return  string
     */

    public function ratings($message = '')
    {
        $this->prep_message($message, true, true);

        //  ----------------------------------------
        //  Begin base url and add as we go
        //  ----------------------------------------

        $base_url = $this->mcp_link(array(
            'method' => __FUNCTION__
        ), false);

        //  ----------------------------------------
        //  Prepare collection filter
        //  ----------------------------------------

        // $collections    = $this->fetch('Rating')
            // ->fields('collection')
            // ->all()
            // ->getDictionary('collection', 'collection');

        $collection_query = ee()->db->select('collection')->group_by('collection')->get('ratings');

        $collections = array();

        foreach ($collection_query->result() as $collection_results) {
            $collections[$collection_results->collection] = $collection_results->collection;
        }

        $collections = ee('CP/Filter')->make('filter_by_collection', 'filter_by_collection', $collections);

        //  ----------------------------------------
        //  Prepare status filter
        //  ----------------------------------------

        $statuses   = array(
            'open'      => lang('open'),
            'closed'    => lang('closed')
        );
        $custom_statuses    = $this->fetch('Rating')
            ->filter('status', 'NOT IN', array('open', 'closed', 'reported'))
            ->all()
            ->getDictionary('status', 'status');

        foreach ($custom_statuses as $status) {
            $statuses[$status]  = ucwords($status);
        }
        $statuses = ee('CP/Filter')->make('filter_by_status', 'filter_by_status', $statuses);

        //  ----------------------------------------
        //  Prepare quarantine filter
        //  ----------------------------------------

        $quarantine = array(
            'reported'      => lang('reported'),
            'quarantined'   => lang('quarantined')
        );
        $quarantine = ee('CP/Filter')->make('filter_by_quarantine', 'filter_by_quarantine', $quarantine);

        //  ----------------------------------------
        //  Prepare rating filter
        //  ----------------------------------------

        $rating_filters = array(
            '>0'    => lang('>0'),
            '>1'    => lang('>1'),
            '>2'    => lang('>2'),
            '>3'    => lang('>3'),
            '>4'    => lang('>4'),
        );
        $rating_filters = ee('CP/Filter')->make('filter_by_rating', 'filter_by_rating', $rating_filters);

        // -------------------------------------
        //  Render filters
        // -------------------------------------

        $filters = ee('CP/Filter')
            ->add('Date')
            ->add($collections)
            ->add($statuses)
            ->add($quarantine)
            ->add($rating_filters);

        $this->cached_vars['filters']   = $filters->render($base_url);

        // -------------------------------------
        //  Add filters to base url
        // -------------------------------------

        $filter_values = $filters->values();
        $base_url->addQueryStringVariables($filter_values);

        //  ----------------------------------------
        //  Query
        //  ----------------------------------------

        $ratings = $this->fetch('Rating')
            ->with('ChannelEntry');

        //  ----------------------------------------
        //  Entry id
        //  ----------------------------------------

        if (ee()->input->get_post('entry_id')) {
            $base_url->addQueryStringVariables(array('entry_id' => ee()->input->get_post('entry_id')));

            $ratings->filter('entry_id', ee()->input->get_post('entry_id'));

            $title  = ee('Model')
                ->get('ChannelEntry', ee()->input->get_post('entry_id'))
                ->first()
                ->title;
        }

        // -------------------------------------
        //  Filter by date
        // -------------------------------------

        if (! empty($filter_values['filter_by_date'])) {
            if (is_array($filter_values['filter_by_date'])) {
                $ratings->filter('rating_date', '>=', $filter_values['filter_by_date'][0]);
                $ratings->filter('rating_date', '<', $filter_values['filter_by_date'][1]);
            } elseif (strlen($filter_values['filter_by_date']) > 8) {
                $ratings->filter('rating_date', '>=', $filter_values['filter_by_date']);
            } else {
                $ratings->filter('rating_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
            }
        }

        // -------------------------------------
        //  Filter by collection
        // -------------------------------------

        if ($collections->value()) {
            $ratings->filter('collection', $collections->value());

            $collection = $this->fetch('Rating')
                ->filter('collection', $collections->value())
                ->first();

            if ($collection) {
                $collection = $collection->collection;
            } else {
                $collection = '';
            }
        }

        // -------------------------------------
        //  Filter by status
        // -------------------------------------

        if ($statuses->value()) {
            $ratings->filter('status', $statuses->value());
        }

        // -------------------------------------
        //  Filter by quarantine
        // -------------------------------------

        if ($quarantine->value()) {
            if ($quarantine->value() == 'quarantined') {
                $ratings->filter('quarantine', 'y');
            } elseif ($quarantine->value() == 'reported') {
                // -------------------------------------
                //  We want ratings that have been reported, but not yet quarantined
                // -------------------------------------

                $reported   = array();

                $quarantines    = $this->fetch('Quarantine')
                    ->filter('status', 'open')
                    ->all();

                foreach ($quarantines as $quarantine) {
                    $reported[] = $quarantine->rating_id;
                }

                if (! empty($reported)) {
                    $ratings->filter('quarantine', 'n');
                    $ratings->filter('rating_id', 'IN', $reported);
                } else {
                    $ratings->filter('quarantine', 'x');    // Fake it. Force this query to never return a result since we found no 'reports'.
                }
            }
        }

        // -------------------------------------
        //  Filter by rating
        // -------------------------------------

        if ($rating_filters->value()) {
            if (strlen($rating_filters->value()) == 1) {
                $ratings->filter('rating', $rating_filters->value());
            } elseif (strlen($rating_filters->value()) == 2) {
                $r  = str_split($rating_filters->value());
                $ratings->filter('rating', $r[0], $r[1]);
            }
        }

        // -------------------------------------
        //  Column sorting
        // -------------------------------------

        $col_map = array(
            'id'            => 'rating_id',
            'rated_entries' => 'rated_entries',
            'collection'    => 'collection',
            'status'        => 'status',
            'name'          => 'name',
            'date'          => 'rating_date',
            'comments'      => 'rating_review_count',
            'rating'        => 'rating',
        );

        if (isset($col_map[ee()->input->get_post('sort_col')])) {
            $sort = (ee()->input->get_post('sort_dir') == 'asc') ? 'ASC' : 'DESC';

            $col = $col_map[ee()->input->get_post('sort_col')];

            if ($col == 'rated_entries') {
                $col    = 'ChannelEntry.title';
            }

            $ratings->order($col, $sort);
        } else {
            $ratings->order('rating_date', 'desc');
        }

        //  ----------------------------------------
        //  Start table
        //  ----------------------------------------

        $tableData = array();

        //  ----------------------------------------
        //  Anything?
        //  ----------------------------------------

        if ($ratings->count() > 0) {
            //  ----------------------------------------
            //  Pagination
            //  ----------------------------------------

            $page   = 0;

            if ($ratings->count() > $this->row_limit) {
                $page   = $this->get_post_or_zero('page') ?: 1;

                $this->cached_vars['pagination'] = ee('CP/Pagination', $ratings->count())
                                    ->displayPageLinks(10)
                                    ->perPage($this->row_limit)
                                    ->currentPage($page)
                                    ->render($base_url);

                $ratings->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
            }

            //  ----------------------------------------
            //  Prepare comment counts
            //  ----------------------------------------

            $rating_ids = array();

            foreach ($ratings->all() as $rating) {
                $rating_ids[]   = $rating->rating_id;
            }

            $sql = "/* Rating ratings() */ SELECT SQL_NO_CACHE SQL_CALC_FOUND_ROWS rating_id, COUNT(review_id) AS comment_total FROM exp_rating_reviews WHERE rating_review != '' AND rating_id IN (" . implode(',', $rating_ids) . ") GROUP BY rating_id";

            $query  = ee()->db->query($sql);

            foreach ($query->result_array() as $row) {
                $comment_totals[$row['rating_id']]  = $row['comment_total'];
            }

            //  ----------------------------------------
            //  Prepare reported counts
            //  ----------------------------------------

            $reported   = array();

            $quarantines    = $this->fetch('Quarantine')
                ->filter('rating_id', 'IN', $rating_ids)
                ->filter('status', 'open')
                ->all();

            foreach ($quarantines as $quarantine) {
                $reported[] = $quarantine->rating_id;
            }

            //  ----------------------------------------
            //  Loop
            //  ----------------------------------------

            foreach ($ratings->all() as $rating) {
                $show_approve_link  = false;
                $class  = '';
                $flagged    = '';

                if ($rating->quarantine == 'y') {
                    $show_approve_link  = true;
                    $class      = 'banned';
                    $flagged    = 'quarantined';
                } elseif (in_array($rating->rating_id, $reported)) {
                    $show_approve_link  = true;
                    $class      = 'pending';
                    $flagged    = 'flagged';
                }

                $tableData[] = array(
                    'attrs'     => array(
                        'class' => $class
                    ),
                    'columns'   => array(
                        $rating->rating_id,
                        array(
                            'content'   => $rating->ChannelEntry->title,
                            'href'      => ($rating->entry_id == 0) ? null : ee('CP/URL')->make('publish/edit/entry/' . $rating->entry_id)
                        ),
                        'quarantined'   => array(
                            'type'      => 'html',
                            'content'   => $this->view('_row', array('col_type' => $flagged))
                        ),
                        array(
                            'content'   => $rating->collection
                        ),
                        $rating->status,
                        $rating->name,
                        $rating->rating,
                        $this->human_time($rating->rating_date),
                        array(
                            'content'   => (empty($comment_totals[$rating->rating_id])) ? 0 : lang('view') . ' (' . $comment_totals[$rating->rating_id] . ')',
                            'href'      => (empty($comment_totals[$rating->rating_id])) ? null : $this->mcp_link(array(
                                'method'    => 'rating_comments',
                                'rating_id' => $rating->rating_id
                            ))
                        ),
                        'manage'    => array(
                            'type'      => 'html',
                            'content'   => $this->view('_row', array(
                                'col_type' => 'manage',
                                'show_approve_link' => $show_approve_link,
                                'approve_link'  => $this->mcp_link(array(
                                    'method'    => 'rating_unquarantine',
                                    'rating_id' => $rating->rating_id
                                )),
                                'link' => $this->mcp_link(array(
                                    'method'    => 'rating_form',
                                    'rating_id' => $rating->rating_id
                                ))
                            ))
                        ),
                        array(
                            'name'      => 'selections[]',
                            'value'     => $rating->rating_id,
                            'data'      => array(
                                'confirm' => lang('rating') . ': <b>' . htmlentities($rating->rating . ' of ' . $rating->ChannelEntry->title . ' on ' . $this->human_time($rating->rating_date), ENT_QUOTES) . '</b>'
                            )
                        )
                    )
                );
            }
        }

        // -------------------------------------
        //  Build table
        // -------------------------------------

        $table = ee('CP/Table', array(
            'sortable'  => true,
            'search'    => false,
            'sort_col'  => 'rating_date',
            'sort_dir'  => 'desc'
        ));

        $cols   = array(
            'id' => array(
                'type'          => Table::COL_ID
            ),
            'entry'         => array(),
            ''      => array(
                'type'  => 'html'
            ),
            'collection'    => '',
            'status'        => array(
                'type'  => Table::COL_STATUS
            ),
            'name'          => '',
            'rating'        => '',
            'date'          => '',
            'comments'      => '',
            'manage'        => array(
                'type'  => 'html'
            ),
            array(
                'type'          => Table::COL_CHECKBOX,
                'name'          => 'selection'
            )
        );

        $table->setColumns($cols);

        $table->setData($tableData);

        $table->setNoResultsText('no_ratings');

        $this->cached_vars['table'] = $table->viewData($base_url);

        // -------------------------------------
        //  Modal for delete confirmation
        // -------------------------------------

        $this->cached_vars['footer'] = array(
            'type'          => 'bulk_action_form',
            'submit_lang'   => lang('submit')
        );

        $this->mcp_modal_confirm(array(
            'form_url'  => $this->mcp_link(array('method' => 'ratings_delete')),
            'name'      => 'ratings',
            'kind'      => lang('ratings'),
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        $page_title = lang('ratings');

        if (isset($title)) {
            $page_title = lang('ratings_for_entry') . ': ' . $title;
        } elseif (isset($collection)) {
            $page_title = lang('ratings_for_collection') . ': ' . $collection;
        }

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'ratings'
            )),
            'cp_page_title'         => $page_title
        );

        $crumbs = array(
            array(lang('ratings'))
        );

        if (isset($title)) {
            $crumbs = array(
                array(
                    lang('ratings'),
                    $this->mcp_link(array('method' => 'ratings'), false)
                ),
                array($title)
            );
        }

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => __FUNCTION__
        ));

        return $this->mcp_view(array(
            'file'      => 'list',
            'highlight' => 'ratings',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => $crumbs
        ));
    }

    // End ratings()

    // --------------------------------------------------------------------

    /**
     * Switch Many Ratings to a new Status
     *
     * @access  public
     * @return  string
     */

    public function mass_status_switcher()
    {
        // --------------------------------------------
        //  Remove 'status_' from the Action
        // --------------------------------------------

        $status = substr(ee()->input->post('action'), 7);

        if (! in_array($status, array('open', 'closed', 'quarantined'))) {
            $this->add_crumb(lang('invalid_request'));
            $this->cached_vars['error_message'] = lang('invalid_request');

            return $this->ee_cp_view('error_page.html');
        }

        // --------------------------------------------
        //  What are we editing?
        // --------------------------------------------

        if (ee()->input->get_post('rating_id') !== false && ctype_digit(ee()->input->get_post('rating_id'))) {
            $_POST['selected'][] = ee()->input->get_post('rating_id');
        }

        if (ee()->input->get_post('selected') === false or ! is_array(ee()->input->get_post('selected'))) {
            return $this->view_ratings();
        }

        $selected = ee()->input->get_post('selected');

        unset($_POST);

        foreach ($selected as $rating_id) {
            $_POST['rating_status'][$rating_id] = $status;
        }

        return $this->edit_ratings();
    }
    // END magic_status_switcher

    // --------------------------------------------------------------------

    /**
     * Edit Rating Comment Form
     *
     * Form for editing a rating comment.
     *
     * @access  public
     * @return  string
     */

    public function edit_rating_comment_form()
    {
        // --------------------------------------------
        //  Allowed to Post/Edit Ratings?
        // --------------------------------------------

        if (! in_array(ee()->session->userdata['group_id'], $this->preference('can_post_ratings'))) {
            $this->add_crumb(lang('invalid_request'));
            $this->cached_vars['error_message'] = lang('not_allowed_to_post_ratings');

            return $this->ee_cp_view('error_page.html');
        }

        // --------------------------------------------
        //  What are we editing?
        // --------------------------------------------

        if (ee()->input->get_post('rating_comment_id') !== false && ctype_digit(ee()->input->get_post('rating_comment_id'))) {
            $_POST['selected'][] = ee()->input->get_post('rating_comment_id');
        }

        if (ee()->input->get_post('selected') === false or ! is_array(ee()->input->get_post('selected'))) {
            return $this->view_ratings();
        }

        $this->cached_vars['rating_id'] = ee()->input->get_post('rating_id');

        // --------------------------------------------
        //  Retrieve Ratings Data
        // --------------------------------------------

        $query = ee()->db->query("SELECT review_id, rating_review FROM exp_rating_reviews
                                  WHERE rating_review != ''
                                  AND review_id IN (" . implode(',', array_map('ceil', ee()->input->get_post('selected'))) . ")");

        if ($query->num_rows() == 0) {
            return $this->view_ratings();
        }

        $this->cached_vars['rating_comments'] = $query->result_array();


        // --------------------------------------------
        //  Prep Breadcrumbs and the like as we have results to display
        // --------------------------------------------

        $this->add_crumb(lang('edit_rating_comments'));
        $this->cached_vars['module_menu_highlight'] = 'module_ratings';

        // --------------------------------------------
        //  Load page
        // --------------------------------------------

        ee()->cp->load_package_js('edit_ratings_form');
        $this->cached_vars['current_page'] = $this->view('edit_rating_comment_form.html', null, true);
        return $this->ee_cp_view('index.html');
    }
    // END edit_rating_comment_form()


    /**
     * Edit Ratings Form
     *
     * Form for editing one or multiple ratings at a time.
     *
     * @access  public
     * @return  string
     */

    public function edit_ratings_form()
    {
        // --------------------------------------------
        //  Allowed to Post/Edit Ratings?
        // --------------------------------------------

        if (! in_array(ee()->session->userdata['group_id'], $this->preference('can_post_ratings'))) {
            $this->add_crumb(lang('invalid_request'));
            $this->cached_vars['error_message'] = lang('not_allowed_to_post_ratings');

            return $this->ee_cp_view('error_page.html');
        }

        // --------------------------------------------
        //  What are we editing?
        // --------------------------------------------

        if (ee()->input->get_post('rating_id') !== false && ctype_digit(ee()->input->get_post('rating_id'))) {
            $_POST['selected'][] = ee()->input->get_post('rating_id');
        }

        if (ee()->input->get_post('selected') === false or ! is_array(ee()->input->get_post('selected'))) {
            return $this->view_ratings();
        }

        // --------------------------------------------
        //  Retrieve Ratings Data
        // --------------------------------------------

        $query = ee()->db->query("SELECT * FROM exp_ratings
                                  WHERE rating_id IN (" . implode(',', array_map('ceil', ee()->input->get_post('selected'))) . ")");

        if ($query->num_rows() == 0) {
            return $this->view_ratings();
        }

        $this->cached_vars['ratings'] = $query->result_array();

        // --------------------------------------------
        //  Retrieve Rating statuses
        // --------------------------------------------

        $this->cached_vars['statuses']['open']          = lang('open');
        $this->cached_vars['statuses']['closed']        = lang('closed');
        $this->cached_vars['statuses']['reported']      = lang('reported_not_quarantined');
        $this->cached_vars['statuses']['quarantined']   = lang('quarantined');

        $query = ee()->db->query("SELECT status FROM exp_ratings WHERE status NOT IN ('" . implode("','", array_keys($this->cached_vars['statuses'])) . "') GROUP BY status ORDER BY status ASC");

        foreach ($query->result_array() as $row) {
            $this->cached_vars['statuses'][$row['status']]  = $row['status'];
        }

        if (! in_array(ee()->session->userdata['group_id'], $this->preference('can_report_ratings'))) {
            unset($this->cached_vars['statuses']['quarantined'], $this->cached_vars['statuses']['Quarantined']);
        }

        // --------------------------------------------
        //  Prep Breadcrumbs and the like as we have results to display
        // --------------------------------------------

        $this->add_crumb(lang('edit_ratings'));
        $this->cached_vars['module_menu_highlight'] = 'module_ratings';

        // --------------------------------------------
        //  Retrieve List of Rating Fields
        // --------------------------------------------

        $this->cached_vars['rating_fields'] = $this->data->get_rating_fields_data();

        if (sizeof($this->cached_vars['ratings']) == 1) {
            $this->cached_vars['selected']['rating_fields'] = array();

            foreach ($this->cached_vars['rating_fields'] as $field_data) {
                $this->cached_vars['selected']['rating_fields'][] = $field_data['field_name'];
            }
        } else {
            $this->cached_vars['selected']['rating_fields'] = array('rating', 'review');
        }

        // --------------------------------------------
        //  Load page
        // --------------------------------------------

        ee()->cp->load_package_js('edit_ratings_form');
        $this->cached_vars['current_page'] = $this->view('edit_ratings_form.html', null, true);
        return $this->ee_cp_view('index.html');
    }
    // END edit_ratings_form()


    // --------------------------------------------------------------------

    /**
     * Edit Ratings Comment Submission
     *
     * @access  public
     * @return  string
     */

    public function edit_rating_comment()
    {
        // --------------------------------------------
        //  Allowed to Post/Edit Ratings?
        // --------------------------------------------

        if (! in_array(ee()->session->userdata['group_id'], $this->preference('can_post_ratings'))) {
            $this->add_crumb(lang('invalid_request'));
            $this->cached_vars['error_message'] = lang('not_allowed_to_post_ratings');

            return $this->ee_cp_view('error_page.html');
        }

        $rating_comment_id = ee()->input->get_post('rating_comment_id', true);

        // --------------------------------------------
        //  Validate Rating Comment ID and Comment
        // --------------------------------------------

        $query = ee()->db->query("SELECT * FROM exp_rating_reviews WHERE review_id IN (" . $rating_comment_id . ")");

        if ($query->num_rows() == 0) {
            return $this->view_ratings();
        }

        $rating_comment = ee()->input->get_post('rating_comment', true);

        if (empty($rating_comment)) {
            $this->show_error(lang('no_rating_comment_submitted'));
        }

        // --------------------------------------------
        //  Let's Update the Data!
        // --------------------------------------------

        $data['rating_review'] = $rating_comment;

        ee()->db->query(ee()->db->update_string('exp_rating_reviews', $data, 'review_id = ' . ee()->db->escape_str($rating_comment_id)));

        //  ----------------------------------------
        //  Going back to previous rating comments, so
        //  set a POST variable so the view_rating_comments
        //  controller doesn't complain
        //  ----------------------------------------

        $_POST['rating_comment_id'] = $rating_comment_id;

        // --------------------------------------------
        //  Success!  Congrats!  Have some Pie!
        // --------------------------------------------

        return $this->view_rating_comments(lang('success_rating_comment_updated'));
    }
    // END edit_rating_comment()


    // --------------------------------------------------------------------

    /**
     * Edit Ratings Submission
     *
     * Submit a ratings.  Right now we only change status or the value of a field, nothing else
     *
     * @access  public
     * @return  string
     */

    public function edit_ratings()
    {
        // --------------------------------------------
        //  Allowed to Post/Edit Ratings?
        // --------------------------------------------

        if (! in_array(ee()->session->userdata['group_id'], $this->preference('can_post_ratings'))) {
            $this->add_crumb(lang('invalid_request'));
            $this->cached_vars['error_message'] = lang('not_allowed_to_post_ratings');

            return $this->ee_cp_view('error_page.html');
        }

        // --------------------------------------------
        //  IDs Taken from Rating Status field - So Check
        // --------------------------------------------

        if (ee()->input->post('rating_status') === false or ! is_array(ee()->input->post('rating_status'))) {
            $this->add_crumb(lang('invalid_request'));
            $this->cached_vars['error_message'] = lang('invalid_request');

            return $this->ee_cp_view('error_page.html');
        }

        if (
            in_array('quarantine', ee()->input->post('rating_status')) &&
            ! in_array(ee()->session->userdata['group_id'], $this->preference('can_report_ratings'))
        ) {
            $this->add_crumb(lang('invalid_request'));
            $this->cached_vars['error_message'] = lang('not_allowed_to_quarantine_ratings');

            return $this->ee_cp_view('error_page.html');
        }

        $rating_ids = array_map('ceil', array_keys(ee()->input->get_post('rating_status')));

        // --------------------------------------------
        //  Validate Rating IDs and Grab Old Status/Quarantine
        // --------------------------------------------

        $query = ee()->db->query("SELECT rating_id, rating_author_id, quarantine, status, entry_id, channel_id
                                  FROM exp_ratings WHERE rating_id IN (" . implode(',', $rating_ids) . ")");

        if ($query->num_rows() == 0) {
            return $this->view_ratings();
        }

        // --------------------------------------------
        //  Let's Process Our Data!
        // --------------------------------------------

        $entries    = array();
        $channels   = array();
        $members    = array();

        foreach ($query->result_array() as $row) {
            $entries[]  = $row['entry_id'];
            $channels[] = $row['channel_id'];
            $members[]  = $row['rating_author_id'];

            $insert = array();

            // --------------------------------------------
            //  Status and Quarantine Update
            // --------------------------------------------

            if ($_POST['rating_status'][$row['rating_id']] == 'quarantined') {
                $insert['quarantine']   = 'y';
                $insert['status']       = 'closed';
            } else {
                $insert['quarantine']   = '';
                $insert['status']       = $_POST['rating_status'][$row['rating_id']];
            }

            if (! empty($insert['quarantine']) && $row['quarantine'] == 'y') {
                ee()->db->query(ee()->db->update_string(
                    'exp_rating_quarantine',
                    array('status' => 'closed',
                                                              'edit_date' => ee()->localize->now ),
                    array( 'rating_id' => $row['rating_id'])
                ));
            }

            // --------------------------------------------
            //  Sticky Update
            // --------------------------------------------

            $insert['sticky']   = 'n';

            if (! empty($_POST['rating_sticky'][$row['rating_id']]) and $_POST['rating_sticky'][$row['rating_id']] == 'y') {
                $insert['sticky']       = 'y';
            }

            // --------------------------------------------
            //  Rating Fields
            // --------------------------------------------

            foreach ($this->data->get_rating_fields_data() as $field_name => $field_data) {
                if (isset($_POST[$field_name][$row['rating_id']])) {
                    if ($field_data['field_type'] == 'number') {
                        if ($_POST[$field_name][$row['rating_id']] == '') {
                            $insert[$field_name] = null;
                        } elseif (is_numeric($_POST[$field_name][$row['rating_id']])) {
                            $insert[$field_name] = ceil($_POST[$field_name][$row['rating_id']]);
                        }
                    } else {
                        $insert[$field_name] = ee()->security->xss_clean($_POST[$field_name][$row['rating_id']]);
                    }
                }
            }

            ee()->db->query(ee()->db->update_string(
                'exp_ratings',
                $insert,
                array( 'rating_id' => $row['rating_id'])
            ));
        }

        // --------------------------------------------
        //  Get ready for stats
        // --------------------------------------------

        $stats  = $this->make('Stat');

        // --------------------------------------------
        //  Update Member's Statistics
        // --------------------------------------------

        $stats->update_member_stats(array_unique($members));

        // --------------------------------------------
        //  Update Channel Statistics
        // --------------------------------------------

        $stats->update_channel_stats(array_unique($channels));

        // ----------------------------------------
        //  Update rating stats
        // ----------------------------------------

        $stats->update_entry_stats(array_unique($entries));

        // --------------------------------------------
        //  Success!  Congrats!  Have some Pie!
        // --------------------------------------------

        return $this->view_ratings(lang('success_ratings_saved'));
    }
    // END edit_ratings()

    // --------------------------------------------------------------------

    /**
     * fields()
     *
     * @access  public
     * @return  string
     */

    public function fields($message = '')
    {
        $this->prep_message($message, true, true);

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        $this->cached_vars['form_right_links']  = array(
            array(
                'link' => $this->mcp_link(array('method' => 'field_form')),
                'title' => lang('create_new_field'),
            )
        );

        //  ----------------------------------------
        //  Query
        //  ----------------------------------------

        $fields = $this->fetch('Field')
            ->order('field_order');

        //  ----------------------------------------
        //  Start table
        //  ----------------------------------------

        $tableData = array();

        //  ----------------------------------------
        //  Anything?
        //  ----------------------------------------

        if ($fields->count() > 0) {
            //  ----------------------------------------
            //  Pagination
            //  ----------------------------------------

            $page   = 0;

            if ($fields->count() > $this->row_limit) {
                $page   = $this->get_post_or_zero('page') ?: 1;

                $mcp_link_array = array(
                    'method' => __FUNCTION__
                );

                $this->cached_vars['pagination'] = ee('CP/Pagination', $fields->count())
                                    ->displayPageLinks(10)
                                    ->perPage($this->row_limit)
                                    ->currentPage($page)
                                    ->render($this->mcp_link($mcp_link_array, false));

                $fields->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
            }

            foreach ($fields->all() as $field) {
                $field_label    = array(
                    'content'   => $field->field_label,
                    'href'      => $this->mcp_link(array(
                        'method'    => 'field_form',
                        'field_id'  => $field->field_id
                    ))
                );

                $disabled   = false;

                if (in_array($field->field_name, array('rating', 'review'))) {
                    $disabled       = true;
                    $field_label    = $field->field_label;
                }

                $tableData[] = array(
                    $field->field_id,
                    $field_label,
                    $field->field_name,
                    ucfirst($field->field_type),
                    ucfirst($field->field_fmt),
                    'manage'    => array(
                        'type'      => 'html',
                        'content'   => $this->view('_row', array('col_type' => 'manage', 'disabled' => $disabled, 'link' => $this->mcp_link(array(
                            'method'    => 'field_form',
                            'field_id'  => $field->field_id
                        ))))),
                    array(
                        'name'      => 'selections[]',
                        'value'     => $field->field_id,
                        'disabled'  => $disabled,
                        'data'      => array(
                            'confirm' => lang('field') . ': <b>' . htmlentities($field->field_label, ENT_QUOTES) . '</b>'
                        )
                    )
                );
            }
        }

        // -------------------------------------
        //  Build table
        // -------------------------------------

        $table = ee('CP/Table', array(
            'sortable'  => false,
            'search'    => false,
        ));

        $table->setColumns(
            array(
                'id' => array(
                    'type'          => Table::COL_ID
                ),
                'field_label'   => array(),
                'field_name'    => array(),
                'field_type'    => array(),
                'field_fmt'     => array(),
                'manage'        => array(
                    'type'  => 'html'
                ),
                array(
                    'type'          => Table::COL_CHECKBOX,
                    'name'          => 'selection'
                )
            )
        );

        $table->setData($tableData);

        $table->setNoResultsText('no_fields');

        $this->cached_vars['table'] = $table->viewData(
            $this->mcp_link(array('method' => __FUNCTION__), false)
        );

        // -------------------------------------
        //  Modal for delete confirmation
        // -------------------------------------

        $this->cached_vars['footer'] = array(
            'type'          => 'bulk_action_form',
            'submit_lang'   => lang('submit')
        );

        $this->mcp_modal_confirm(array(
            'form_url'  => $this->mcp_link(array('method' => 'fields_delete')),
            'name'      => 'fields',
            'kind'      => lang('fields'),
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'field_update'
            )),
            'cp_page_title'         => lang('fields'),
            'table_head'            => lang('fields')
        );

        return $this->mcp_view(array(
            'file'      => 'list',
            'highlight' => 'fields',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(lang('fields'))
            )
        ));
    }

    // END fields()

    // --------------------------------------------------------------------

    /**
     * fields_delete
     *
     * @access  public
     * @param   array   $post
     * @return  string  redirect message
     */

    public function fields_delete()
    {
        //---------------------------------------------
        //  Make the model do the work
        //---------------------------------------------

        $this->make('Field')
            ->delete_fields($_POST['selections']);

        //---------------------------------------------
        //  Redirect
        //---------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'fields',
            'msg'       => 'fields_deleted'
        )));
    }

    //  End fields_delete()

    // --------------------------------------------------------------------

    /**
     * field_form()
     *
     * @access  public
     * @return  string
     */

    public function field_form($message = '')
    {
        $this->prep_message($message);

        // --------------------------------------------
        //  Current Values
        // --------------------------------------------

        $prefModel      = $this->make('Field');
        $defaultPrefs   = $prefModel->default_prefs;
        $order          = 1;

        if (ee()->input->get('field_id')) {
            $prefs = $this->fetch('Field')
                ->filter('field_id', ee()->input->get('field_id'))
                ->first()
                ->toArray();

            $order  = $prefs['field_order'];
        } else {
            // --------------------------------------------
            //  Grab next order
            // --------------------------------------------

            $order  = $this->fetch('Field')
                ->order('field_order', 'desc')
                ->first();

            $order  = ($order) ? $order->field_order + 1 : $order;
        }

        // --------------------------------------------
        //  Start sections
        // --------------------------------------------

        $sections = array();
        $main_section = array();

        foreach ($defaultPrefs as $short_name => $data) {
            $desc_name  = $short_name . '_subtext';
            $desc       = lang($desc_name);

            //if we don't have a description don't set it
            $desc       = ($desc !== $desc_name) ? $desc : '';

            $required   = false;

            //set order
            $value      = isset($prefs[$short_name]) ? $prefs[$short_name] : $data['default'];
            $value      = ($short_name == 'field_order') ? $order : $value;

            $fields     = array(
                $short_name => array_merge($data, array(
                    'value' => $value
                ))
            );

            // --------------------------------------------
            //  Set the row now
            // --------------------------------------------

            $main_section[$short_name] = array(
                'wide'      => isset($wide),
                'hide'      => ($data['type'] == 'hidden') ? true : false,
                'title'     => lang($short_name),
                'desc'      => $desc,
                'group'     => (empty($data['group'])) ? 'default' : $data['group'],
                'fields'    => $fields
            );
        }

        $sections[] = $main_section;

        $this->cached_vars['sections'] = $sections;

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => 'field_update'
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        $new_update = (ee()->input->get('field_id')) ? 'field_update' : 'field_add';

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'field_update'
            )),
            'cp_page_title'         => lang($new_update),
            'save_btn_text'         => 'btn_save_field',
            'save_btn_text_working' => 'btn_saving_field'
        );

        return $this->mcp_view(array(
            'file'      => 'form',
            'highlight' => 'fields',
            'pkg_js'    => array('rating'),
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(
                    lang('fields'),
                    $this->mcp_link(array('method' => 'fields'), false)
                ),
                array(lang($new_update))
            )
        ));
    }

    //  End field_form()

    // --------------------------------------------------------------------

    /**
     * field_update()
     *
     * @access  public
     * @return  bool
     */

    public function field_update()
    {
        // --------------------------------------------
        //  We need the model no matter what
        // --------------------------------------------

        $field = $this->make('Field');
        $prefs = $this->make('Field');
        $default_prefs = $prefs->default_prefs;

        $input_keys = $required = array_keys($default_prefs);

        // --------------------------------------------
        //  If edit mode, reset the focker
        // --------------------------------------------

        $fieldId = ee()->input->post('field_id');
        if (! empty($fieldId)) {
            $field  = $this->fetch('Field')
                ->filter('field_id', $fieldId)
                ->first();

            $old_field_name = $field->field_name;
        }

        // --------------------------------------------
        //  Populate with $_POST
        // --------------------------------------------

        $field->set($_POST);

        // --------------------------------------------
        //  validate (custom method)
        // --------------------------------------------

        $result = $prefs->validateDefaultPrefs($field->asArray(), $required);

        if (! $result->isValid()) {
            $errors = array();

            foreach ($result->getAllErrors() as $name => $error_list) {
                foreach ($error_list as $error_name => $error_msg) {
                    $errors[] = lang($name) . ': ' . $error_msg;
                }
            }

            return $this->show_error($errors);
        }

        // --------------------------------------------
        //  Save
        // --------------------------------------------

        $field->save();

        // ----------------------------------------
        //  Set field type
        // ----------------------------------------

        if (ee()->input->get_post('field_type') == 'text') {
            $field_type = "VARCHAR(" . ceil(ee()->input->get_post('field_maxl')) . ") NULL DEFAULT ''";
        } elseif (ee()->input->get_post('field_type') == 'number') {
            if (ee()->input->get_post('field_maxl') < 3) {
                $field_type = "TINYINT UNSIGNED NOT NULL DEFAULT 0";
            } else {
                $field_type = "INT UNSIGNED NOT NULL DEFAULT 0";
            }
        } else {
            $field_type = "TEXT";
        }

        if (! empty($fieldId)) {
            ee()->db->query("ALTER TABLE exp_ratings
                             CHANGE `" . ee()->db->escape_str($old_field_name) . "`
                             `" . ee()->db->escape_str($field->field_name) . "`
                             " . $field_type);
        } else {
            ee()->db->query("ALTER TABLE exp_ratings ADD " . ee()->db->escape_str($field->field_name) . " " . $field_type);

            ee()->db->query("ALTER TABLE exp_rating_stats ADD `" . ee()->db->escape_str('count_' . $field->field_id) . "` " . "INT UNSIGNED NULL DEFAULT NULL");

            ee()->db->query("ALTER TABLE exp_rating_stats ADD `" . ee()->db->escape_str('sum_' . $field->field_id) . "` " . "INT UNSIGNED NULL DEFAULT NULL");

            ee()->db->query("ALTER TABLE exp_rating_stats ADD `" . ee()->db->escape_str('avg_' . $field->field_id) . "` " . "FLOAT UNSIGNED NULL DEFAULT NULL");
        }

        // --------------------------------------------
        //  Return view
        // --------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'fields',
            'msg'       => 'field_updated'
        )));
    }

    //  End field_update()

    // --------------------------------------------------------------------

    /**
     * templates()
     *
     * @access  public
     * @return  string
     */

    public function templates($message = '')
    {
        $this->prep_message($message, true, true);

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        $this->cached_vars['form_right_links']  = array(
            array(
                'link' => $this->mcp_link(array('method' => 'template_form')),
                'title' => lang('create_new_template'),
            )
        );

        //  ----------------------------------------
        //  Query
        //  ----------------------------------------

        $templates = $this->fetch('Template');

        //  ----------------------------------------
        //  Start table
        //  ----------------------------------------

        $tableData = array();

        //  ----------------------------------------
        //  Anything?
        //  ----------------------------------------

        if ($templates->count() > 0) {
            //  ----------------------------------------
            //  Pagination
            //  ----------------------------------------

            $page   = 0;

            if ($templates->count() > $this->row_limit) {
                $page   = $this->get_post_or_zero('page') ?: 1;

                $mcp_link_array = array(
                    'method' => __FUNCTION__
                );

                $this->cached_vars['pagination'] = ee('CP/Pagination', $templates->count())
                                    ->displayPageLinks(10)
                                    ->perPage($this->row_limit)
                                    ->currentPage($page)
                                    ->render($this->mcp_link($mcp_link_array, false));

                $templates->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
            }

            foreach ($templates->all() as $template) {
                $disabled   = (in_array($template->template_name, array('default_template'))) ? true : false;

                $tableData[] = array(
                    $template->template_id,
                    array(
                        'content'   => $template->template_label,
                        'href'      => $this->mcp_link(array(
                            'method'    => 'template_form',
                            'template_id'   => $template->template_id
                        ))
                    ),
                    $template->template_name,
                    'manage'    => array(
                        'type'      => 'html',
                        'content'   => $this->view('_row', array('col_type' => 'manage', 'disabled' => $disabled, 'link' => $this->mcp_link(array(
                            'method'    => 'template_form',
                            'template_id'   => $template->template_id
                        ))))),
                    array(
                        'name'      => 'selections[]',
                        'value'     => $template->template_id,
                        'disabled'  => $disabled,
                        'data'      => array(
                            'confirm' => lang('template') . ': <b>' . htmlentities($template->template_label, ENT_QUOTES) . '</b>'
                        )
                    )
                );
            }
        }

        // -------------------------------------
        //  Build table
        // -------------------------------------

        $table = ee('CP/Table', array(
            'sortable'  => false,
            'search'    => false,
        ));

        $table->setColumns(
            array(
                'id' => array(
                    'type'          => Table::COL_ID
                ),
                'label'         => array(),
                'template_name' => array(),
                'manage'        => array(
                    'type'  => 'html'
                ),
                array(
                    'type'          => Table::COL_CHECKBOX,
                    'name'          => 'selection'
                )
            )
        );

        $table->setData($tableData);

        $table->setNoResultsText('no_templates');

        $this->cached_vars['table'] = $table->viewData(
            $this->mcp_link(array('method' => __FUNCTION__), false)
        );

        //---------------------------------------------
        //  Modal for delete confirmation
        //---------------------------------------------

        $this->cached_vars['footer'] = array(
            'type'          => 'bulk_action_form',
            'submit_lang'   => lang('submit')
        );

        $this->mcp_modal_confirm(array(
            'form_url'  => $this->mcp_link(array('method' => 'templates_delete')),
            'name'      => 'templates',
            'kind'      => lang('templates'),
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'template_update'
            )),
            'cp_page_title'         => lang('notification_templates'),
            'table_head'            => lang('notification_templates')
        );

        return $this->mcp_view(array(
            'file'      => 'list',
            'highlight' => 'templates',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(lang('notification_templates'))
            )
        ));
    }

    // END templates()

    // --------------------------------------------------------------------

    /**
     * templates_delete
     *
     * @access  public
     * @param   array   $post
     * @return  string  redirect message
     */

    public function templates_delete()
    {
        //---------------------------------------------
        //  Make the model do the work
        //---------------------------------------------

        $this->fetch('Template', $_POST['selections'])
            ->delete();

        //---------------------------------------------
        //  Redirect
        //---------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'templates',
            'msg'       => 'templates_deleted'
        )));
    }

    //  End templates_delete()

    // --------------------------------------------------------------------

    /**
     * template_form()
     *
     * @access  public
     * @return  string
     */

    public function template_form($message = '')
    {
        $this->prep_message($message);

        // --------------------------------------------
        //  Current Values
        // --------------------------------------------

        $prefModel      = $this->make('Template');
        $defaultPrefs   = $prefModel->default_prefs;

        if (ee()->input->get('template_id')) {
            $prefs = $this->fetch('Template')
                ->filter('template_id', ee()->input->get('template_id'))
                ->first()
                ->toArray();
        }

        // --------------------------------------------
        //  Start sections
        // --------------------------------------------

        $sections = array();
        $main_section = array();

        foreach ($defaultPrefs as $short_name => $data) {
            $desc_name  = $short_name . '_subtext';
            $desc       = lang($desc_name);

            //if we don't have a description don't set it
            $desc       = ($desc !== $desc_name) ? $desc : '';

            $required   = (! empty($data['required']));
            $disabled   = '';

            if (isset($prefs) and $prefs['template_id'] == 1 and ($short_name == 'template_name' or $short_name == 'template_label')) {
                $disabled   = ' readonly="readonly"';
            }

            $fields     = array(
                $short_name => array_merge($data, array(
                    'value'     => isset($prefs[$short_name]) ?
                                    $prefs[$short_name] :
                                    $data['default'],
                    //we just require everything
                    //its a settings form
                    'attrs'     => $disabled,
                    'required'  => $required
                ))
            );

            // --------------------------------------------
            //  Set the row now
            // --------------------------------------------

            $main_section[$short_name] = array(
                'wide'      => isset($wide),
                'hide'      => ($data['type'] == 'hidden') ? true : false,
                'title'     => lang($short_name),
                'desc'      => $desc,
                'group'     => (empty($data['group'])) ? 'default' : $data['group'],
                'fields'    => $fields
            );
        }

        $sections[] = $main_section;

        $this->cached_vars['sections'] = $sections;

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => 'template_update'
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        $new_update = (ee()->input->get('template_id')) ? 'template_update' : 'template_add';

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'template_update'
            )),
            'cp_page_title'         => lang($new_update),
            'save_btn_text'         => 'btn_save_template',
            'save_btn_text_working' => 'btn_saving_template'
        );

        return $this->mcp_view(array(
            'file'      => 'form',
            'highlight' => 'templates',
            'pkg_js'    => array('rating'),
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(
                    lang('notification_templates'),
                    $this->mcp_link(array('method' => 'templates'), false)
                ),
                array(lang($new_update))
            )
        ));
    }

    //  End template_form()

    // --------------------------------------------------------------------

    /**
     * template_update()
     *
     * @access  public
     * @return  bool
     */

    public function template_update()
    {
        $prefs = $this->make('Template');
        $default_prefs = $prefs->default_prefs;

        $input_keys = $required = array_keys($default_prefs);

        $inputs = array();

        // --------------------------------------------
        //  fetch only default prefs
        // --------------------------------------------

        foreach ($input_keys as $input) {
            if (isset($_POST[$input])) {
                if (is_array($_POST[$input])) {
                    $inputs[$input] = implode('|', $_POST[$input]);
                } else {
                    $inputs[$input] = ee()->input->post($input);
                }
            }
        }

        // --------------------------------------------
        //  validate (custom method)
        // --------------------------------------------

        $result = $prefs->validateDefaultPrefs($inputs, $required);

        if (! $result->isValid()) {
            $errors = array();

            foreach ($result->getAllErrors() as $name => $error_list) {
                foreach ($error_list as $error_name => $error_msg) {
                    $errors[] = lang($name) . ': ' . $error_msg;
                }
            }

            return $this->show_error($errors);
        }

        // --------------------------------------------
        //  Update / Create
        // --------------------------------------------

        $templateId = ee()->input->post('template_id');
        if (! empty($templateId)) {
            $template = $this->fetch('Template')
                ->filter('template_id', $templateId)
                ->first();
        } else {
            $template = $this->make('Template');
        }

        foreach ($inputs as $name => $value) {
            $template->$name = ee()->input->post($name);
        }

        $template->save();

        // --------------------------------------------
        //  Return view
        // --------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'templates',
            'msg'       => 'template_updated'
        )));
    }

    //  End template_update()

    // --------------------------------------------------------------------

    /**
     * preferences()
     *
     * @access  public
     * @param   string
     * @return  string
     */

    public function preferences($message = '')
    {
        $this->prep_message($message, true, true);

        // --------------------------------------------
        //  Current Values
        // --------------------------------------------

        $prefModel      = $this->make('Preference');
        $defaultPrefs   = $prefModel->default_prefs;

        $prefs = $this->fetch('Preference')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all()->getDictionary(
                'preference_name',
                'preference_value'
            );

        // --------------------------------------------
        //  Start sections
        // --------------------------------------------

        $sections   = array();

        foreach ($defaultPrefs as $short_name => $data) {
            $desc_name  = $short_name . '_subtext';
            $desc       = lang($short_name . '_subtext');

            //if we don't have a description don't set it
            $desc       = ($desc !== $desc_name) ? $desc : '';

            $required   = (! empty($data['required']));

            $fields     = array(
                $short_name => array_merge($data, array(
                    'value'     => isset($prefs[$short_name]) ?
                                    $prefs[$short_name] :
                                    $data['default'],
                    //we just require everything
                    //its a settings form
                    'required'  => $required
                ))
            );

            // --------------------------------------------
            //  Special handling for channel
            // --------------------------------------------

            if ($data['type'] == 'multiselect|channel') {
                // --------------------------------------------
                //  Channels
                // --------------------------------------------

                $channels = ee('Model')
                    ->get('Channel')
                    ->filter('site_id', ee()->config->item('site_id'))
                    ->order('channel_title', 'asc')
                    ->all();

                $selectedChannels = isset($prefs[$short_name]) ? $prefs[$short_name] : $data['default'];

                $channelChoices = ['all' => lang('all_channels')];
                foreach ($channels as $channel) {
                    $channelChoices[$channel->channel_id] = $channel->channel_title;
                }

                $fields[$short_name] = array(
                    'type'      => 'checkbox',
                    'name'      => $short_name,
                    'choices'   => $channelChoices,
                    'value'     => $selectedChannels,
                );
            }

            // --------------------------------------------
            //  Special handling for category_group
            // --------------------------------------------

            if ($data['type'] == 'multiselect|member_group') {
                // --------------------------------------------
                //  Category groups
                // --------------------------------------------

                $selectedGroups = isset($prefs[$short_name]) ? $prefs[$short_name] : $data['default'];

                if (version_compare(APP_VER, '6.0', '>=')) {
                    $groupChoices = ee('Model')->get('Role')
                        ->order('name', 'asc')
                        ->all()
                        ->getDictionary('role_id', 'name');
                } else {
                    $groups = ee('Model')
                        ->get('MemberGroup')
                        ->filter('site_id', ee()->config->item('site_id'))
                        ->order('group_title', 'asc')
                        ->all();

                    $groupChoices = [];
                    foreach ($groups as $group) {
                        $groupChoices[$group->group_id] = $group->group_title;
                    }
                }

                $fields[$short_name]    = array(
                    'type'      => 'checkbox',
                    'name'      => $short_name,
                    'choices'   => $groupChoices,
                    'value'     => $selectedGroups
                );
            }

            // --------------------------------------------
            //  Set the row now
            // --------------------------------------------

            $main_section[$short_name] = array(
                'title'     => lang($short_name),
                'desc'      => $desc,
                'caution'   => (! empty($data['caution'])),
                'fields'    => $fields
            );
        }

        $sections[] = $main_section;

        $this->cached_vars['sections'] = $sections;

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => 'preferences_update'
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'preferences_update'
            )),
            'cp_page_title'         => lang('Preferences'),
            'save_btn_text'         => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        return $this->mcp_view(array(
            'file'      => 'form',
            'highlight' => 'preferences',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(lang('preferences'))
            )
        ));
    }

    // END preferences()

    // --------------------------------------------------------------------

    /**
     * preferences_update()
     *
     * @access  public
     * @param   string
     * @return  string
     */

    public function preferences_update()
    {
        $prefs = $this->make('Preference');
        $default_prefs = $prefs->default_prefs;

        $input_keys = $required = array_keys($default_prefs);

        $inputs = array();

        // --------------------------------------------
        //  fetch only default prefs
        // --------------------------------------------

        foreach ($default_prefs as $key => $val) {
            if (strpos($val['type'], 'multiselect') !== false) {
                $inputs[$key]   = $val['default'];
            }

            if (isset($_POST[$key])) {
                if (is_array($_POST[$key])) {
                    $inputs[$key] = $_POST[$key];
                } else {
                    $inputs[$key] = ee()->input->post($key);
                }
            }
        }

        // --------------------------------------------
        //  validate (custom method)
        // --------------------------------------------

        $result = $prefs->validateDefaultPrefs($inputs, $required);

        if (! $result->isValid()) {
            $errors = array();

            foreach ($result->getAllErrors() as $name => $error_list) {
                foreach ($error_list as $error_name => $error_msg) {
                    $errors[] = lang($name) . ': ' . $error_msg;
                }
            }

            return $this->show_error($errors);
        }

        // --------------------------------------------
        //  Update Preferences
        // --------------------------------------------

        $currentPrefs = $this->fetch('Preference')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all()
            ->indexBy('preference_name');

        foreach ($inputs as $name => $value) {
            $value  = (is_array($value)) ? implode('|', $value) : $value;

            //update
            if (isset($currentPrefs[$name])) {
                $currentPrefs[$name]->preference_value = $value;
                $currentPrefs[$name]->save();
            } else {
                //insert
                $new = $this->make('Preference');
                $new->preference_value = $value;
                $new->preference_name = $name;
                $new->site_id = ee()->config->item('site_id');
                $new->save();
            }
        }

        // --------------------------------------------
        //  Return view
        // --------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'preferences',
            'msg'       => 'preferences_updated'
        )));
    }

    // END preferences_update()

    // --------------------------------------------------------------------

    /**
     *  Export Ratings
     *
     *  Exports a list of ratings from the current search parameters - View Ratings page only
     *
     *  @access     public
     *  @return     string
     */

    public function export_ratings($data)
    {
        if (empty($data)) {
            exit('');
        }

        // ----------------------------------------
        //  Build the output header
        // ----------------------------------------

        ob_start();

        // ----------------------------------------
        //  Create header
        // ----------------------------------------

        echo $this->csv_output(lang('count')) . $this->csv_separator;

        echo $this->csv_output(lang('rating_status')) . $this->csv_separator;

        echo $this->csv_output(lang('quarantined')) . $this->csv_separator;

        echo $this->csv_output(lang('date')) . $this->csv_separator;

        echo $this->csv_output(lang('collection')) . $this->csv_separator;

        echo $this->csv_output(lang('entry_id')) . $this->csv_separator;

        echo $this->csv_output(lang('raters_name')) . $this->csv_separator;

        echo $this->csv_output(lang('email')) . $this->csv_separator;

        foreach ($this->data->get_rating_fields_list() as $field_name => $field_label) {
            echo $this->csv_output($field_label) . $this->csv_separator;
        }

        // ----------------------------------------
        //  Create body
        // ----------------------------------------

        $row_count = 0;

        foreach ($data as $row) {
            echo "\n";

            echo $this->csv_output($row_count) . $this->csv_separator;

            echo $this->csv_output(ucfirst($row['status'])) . $this->csv_separator;

            echo $this->csv_output(($row['quarantine'] == 'y') ? 'y' : 'n') . $this->csv_separator;

            echo $this->csv_output($this->human_time($row['rating_date'])) . $this->csv_separator;

            echo $this->csv_output(($row['collection'] == '') ? 'empty' : $row['collection']) . $this->csv_separator;

            echo $this->csv_output($row['entry_id']) . $this->csv_separator;

            echo $this->csv_output($row['name']) . $this->csv_separator;

            echo $this->csv_output($row['email']) . $this->csv_separator;

            foreach ($this->data->get_rating_fields_list() as $field_name => $field_label) {
                echo $this->csv_output($row[$field_name]) . $this->csv_separator;
            }

            $row_count++;
        }

        // ----------------------------------------
        //  Return the finalized output
        // ----------------------------------------

        $buffer = ob_get_contents();

        ob_end_clean();

        $name       = (ee()->input->get_post('collection')) ?
                        ee()->input->get_post('collection') :
                        'Ratings_Export';

        $func = (is_callable(array(ee()->localize, 'format_date'))) ?
                                    'format_date' :
                                    'decode_date';

        $filename   = str_replace(" ", "_", $name) . '_' .
                        ee()->localize->$func('%Y%m%d', time());

        ee()->load->library('zip');
        ee()->zip->add_data($filename . '.csv', $buffer);
        ee()->zip->download($filename . '.zip');
        ee()->zip->clear_data();

        exit;
    }
    //  End export entries

    // --------------------------------------------------------------------

    /**
     * rating_comments_delete()
     *
     * @access  public
     * @param   array   $post
     * @return  string  redirect message
     */

    public function rating_comments_delete()
    {
        //---------------------------------------------
        //  Get ratings
        //---------------------------------------------

        $rating_ids = $this->fetch('Review')
            ->filter('review_id', 'IN', $_POST['selections'])
            ->all()
            ->getDictionary('rating_id', 'rating_id');

        //---------------------------------------------
        //  Make the model do the work
        //---------------------------------------------

        $this->fetch('Review', $_POST['selections'])
            ->delete();

        //---------------------------------------------
        //  Recount
        //---------------------------------------------

        $this->make('Review')
            ->recount_rating_reviews($rating_ids);

        //---------------------------------------------
        //  Redirect
        //---------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'ratings',
            'msg'       => 'comments_deleted'
        )));
    }

    //  End rating_comments_delete()

    // --------------------------------------------------------------------

    /**
     * rating_comment_form()
     *
     * @access  public
     * @return  string
     */

    public function rating_comment_form($message = '')
    {
        $this->prep_message($message, true, true);

        // --------------------------------------------
        //  Current Values
        // --------------------------------------------

        $prefModel      = $this->make('Review');
        $defaultPrefs   = $prefModel->default_prefs;

        if (ee()->input->get('review_id')) {
            $prefs = $this->fetch('Review', ee()->input->get('review_id'))
                ->first()
                ->asArray();
        }

        // --------------------------------------------
        //  Start sections
        // --------------------------------------------

        $sections = array();
        $main_section = array();

        foreach ($defaultPrefs as $short_name => $data) {
            $desc_name  = $short_name . '_subtext';
            $desc       = lang($desc_name);

            //if we don't have a description don't set it
            $desc       = ($desc !== $desc_name) ? $desc : '';

            $fields     = array(
                $short_name => array_merge($data, array(
                    'value'     => isset($prefs[$short_name]) ? $prefs[$short_name] : $data['default'],
                ))
            );

            // --------------------------------------------
            //  Set the row now
            // --------------------------------------------

            $main_section[$short_name] = array(
                'hide'      => ($data['type'] == 'hidden') ? true : false,
                'title'     => lang($short_name),
                'desc'      => $desc,
                'fields'    => $fields
            );
        }

        $sections[] = $main_section;

        $this->cached_vars['sections'] = $sections;

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => 'rating_comment_update'
        ));

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'rating_comment_update'
            )),
            'cp_page_title'         => lang('rating_comment_update'),
            'save_btn_text'         => 'btn_save_comment',
            'save_btn_text_working' => 'btn_saving_comment'
        );

        return $this->mcp_view(array(
            'file'      => 'form',
            'highlight' => 'ratings',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(
                    lang('ratings'),
                    $this->mcp_link(array('method' => 'ratings'), false)
                ),
                array(lang('rating_comment_update'))
            )
        ));
    }

    //  End rating_comment_form()

    // --------------------------------------------------------------------

    /**
     * rating_comment_update()
     *
     * @access  public
     * @return  bool
     */

    public function rating_comment_update()
    {
        $prefs = $this->make('Review');
        $default_prefs = $prefs->default_prefs;

        $input_keys = $required = array_keys($default_prefs);

        $inputs = array();

        // --------------------------------------------
        //  fetch only default prefs
        // --------------------------------------------

        foreach ($input_keys as $input) {
            if (isset($_POST[$input])) {
                if (is_array($_POST[$input])) {
                    $inputs[$input] = implode('|', $_POST[$input]);
                } else {
                    $inputs[$input] = ee()->input->post($input);
                }
            }
        }

        // --------------------------------------------
        //  validate (custom method)
        // --------------------------------------------

        $result = $prefs->validateDefaultPrefs($inputs, $required);

        if (! $result->isValid()) {
            $errors = array();

            foreach ($result->getAllErrors() as $name => $error_list) {
                foreach ($error_list as $error_name => $error_msg) {
                    $errors[] = lang($name) . ': ' . $error_msg;
                }
            }

            return $this->show_error($errors);
        }

        // --------------------------------------------
        //  Update
        // --------------------------------------------

        $reviewId = ee()->input->post('review_id');
        if (! empty($reviewId)) {
            $review = $this->fetch('Review', $reviewId)
                ->first();

            foreach ($inputs as $name => $value) {
                $review->$name = ee()->input->post($name);
            }

            $review->save();
        }

        // --------------------------------------------
        //  Return view
        // --------------------------------------------

        return ee()->functions->redirect($this->mcp_link(array(
            'method'    => 'rating_comment_form',
            'review_id' => $review->review_id,
            'msg'       => 'comment_updated'
        )));
    }

    //  End rating_comment_update()

    // --------------------------------------------------------------------

    /**
     *  CSV Output Formatter
     *
     *  Preps any CSV for Output based on the delimiting character
     *
     *  @access     public
     *  @param      string
     *  @return     string
     */
    public function csv_output($str)
    {
        $str = trim($str);

        if (stristr($str, '"')) {
            $str = '"' . str_replace('"', '""', $str) . '"';
        } elseif (stristr($str, "\n") or stristr($str, "\r") or stristr($str, ',')) {
            $str = '"' . $str . '"';
        }

        return $str;
    }
    // END csv_output()

    // --------------------------------------------------------------------

    /**
     *  Maintenance Page in CP
     *
     *  Not quite sure what this is going to do yet.  Rating has gone through many revisions and
     *  I suspect I am going to keep on changing things in my Bridge conversion -Paul
     *
     *  @access     public
     *  @return     string
     */

    public function utilities()
    {
        $this->prep_message();

        // --------------------------------------------
        //  Prepare for processing
        // --------------------------------------------

        $limit  = 10; // Number of entries/channels/members to process at a time.
        $total  = 0;

        // --------------------------------------------
        //  Should we process?
        // --------------------------------------------

        $run    = (isset($_GET['row'])) ? true : false;
        $row    = (! ee()->input->get_post('row')) ? 0 : ceil(ee()->input->get_post('row'));

        // --------------------------------------------
        //  Pass to handler
        // --------------------------------------------

        $return = $this->make('Stat')
            ->recount_stats($row, $limit, $run);

        extract($return);

        // --------------------------------------------
        //  Housekeeping
        // --------------------------------------------

        $total_done     = (! $run) ? 0 : $row;                  // Total DB rows completed
        $row            = (! $run) ? 0 : $row + $limit;     // Next DB row to start from, used in URL
        $next_batch     = (! $run) ? 1 : ceil($row / $limit) + 1; // Which batch are we on?
        $total_batches  = ceil($total / $limit);                          // Total batches to do.

        // --------------------------------------------
        //  Set the message and content
        // --------------------------------------------

        $url    = array(
            'method'    => 'utilities',
            'row'       => $row,
        );

        $button         = 'btn_recount_now';
        $button_working = 'btn_recounting_now';

        if (empty($total)) {
            $content    = '<p>' . lang('no_batches') . '</p>';
        } elseif ($next_batch > $total_batches) {
            $content    = '<p>' . lang('recount_complete') . '</p>';
            unset($url['row']);
            $button         = 'btn_recount_complete';
            $button_working = 'btn_recount_complete_working';
        } else {
            $content        = '<p>' . lang('next_batch') . ': ' . $next_batch . '<br />' . lang('total_batches') . ': ' . $total_batches . '</p>';

            if ($run) {
                $button         = 'btn_continue_recount';
                $button_working = 'btn_continuing_recount';
            }
        }

        $main_section['recount_statistics'] = array(
            'title'     => lang('recount_statistics'),
            'desc'      => lang('recount_statistics_subtext'),
            'fields'    => array(
                'recount_statistics'    => array(
                    'type'      => 'html',
                    'content'   => $content
                )
            )
        );

        $this->cached_vars['sections'][] = $main_section;

        $this->cached_vars['form_url'] = $this->mcp_link($url);

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        $this->cached_vars += array(
            'base_url'              => $this->mcp_link($url),
            'cp_page_title'         => lang('recount_statistics'),
            'save_btn_text'         => $button,
            'save_btn_text_working' => $button_working,
        );

        return $this->mcp_view(array(
            'file'      => 'form',
            'highlight' => 'utilities',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(lang('recount_statistics'))
            )
        ));




        // --------------------------------------------
        //  Limits and Totals for Processing
        // --------------------------------------------

        if ($start !== true) {
            $row            = ceil(ee()->input->get_post('row'));

            // --------------------------------------------
            //  Remove Stats for Non-Rated Entries
            // --------------------------------------------

            if ($row == 0) {
                ee()->db->query("DELETE FROM exp_ratings WHERE entry_id != 0 AND entry_id NOT IN (SELECT entry_id FROM exp_channel_titles)");

                ee()->db->query("DELETE FROM exp_rating_stats WHERE entry_id != 0 AND entry_id NOT IN (SELECT entry_id FROM exp_ratings)");

                ee()->db->query("DELETE FROM exp_rating_stats WHERE channel_id != 0 AND channel_id NOT IN (SELECT channel_id FROM exp_ratings)");
            }

            // --------------------------------------------
            //  Entry Statistics
            // --------------------------------------------

            $query          = ee()->db->query("SELECT COUNT(DISTINCT entry_id) AS total_count FROM exp_ratings");
            $query_row      = $query->row_array();
            $total          = $query_row['total_count'];

            $query          = ee()->db->query("SELECT DISTINCT entry_id FROM exp_ratings ORDER BY entry_id LIMIT {$row}, {$limit}");

            foreach ($query->result_array() as $data_row) {
                // How damn! Look at that abstraction, baby!
                $this->lib('Utils')->update_entry_stats($data_row['entry_id']);
            }

            // --------------------------------------------
            //  Member Statistics
            // --------------------------------------------

            $query          = ee()->db->query("SELECT COUNT(DISTINCT rating_author_id) AS total_count FROM exp_ratings");
            $query_row      = $query->row_array();
            $total          = ($total < $query_row['total_count']) ? $query_row['total_count'] : $total;

            $query          = ee()->db->query("SELECT DISTINCT rating_author_id FROM exp_ratings ORDER BY rating_author_id LIMIT {$row}, {$limit}");

            foreach ($query->result_array() as $data_row) {
                $this->lib('Utils')->update_member_stats($data_row['rating_author_id']);
            }

            // --------------------------------------------
            //  Channel/Weblog Statistics
            // --------------------------------------------

            $query          = ee()->db->query("SELECT COUNT(DISTINCT channel_id) AS total_count FROM exp_ratings");
            $query_row      = $query->row_array();
            $total          = ($total < $query_row['total_count']) ? $query_row['total_count'] : $total;

            $query          = ee()->db->query("SELECT DISTINCT channel_id FROM exp_ratings ORDER BY channel_id LIMIT {$row}, {$limit}");

            foreach ($query->result_array() as $data_row) {
                $this->lib('Utils')->update_channel_stats($data_row['channel_id']);
            }
        }

        // --------------------------------------------
        //  Form Variables
        // --------------------------------------------

        $this->cached_vars['start']         = $start;

        $this->cached_vars['row']           = ($start === true) ? 0 : $row + $limit;        // Next DB row to start from, used in URL
        $this->cached_vars['total_done']    = ($start === true) ? 0 : $row;                 // Total DB rows completed
        $this->cached_vars['total']         = $total;                                       // Total DB rows to do

        $this->cached_vars['next_batch']    = ($start === true) ? 1 : ceil($this->cached_vars['row'] / $limit) + 1;   // Which batch are we on?
        $this->cached_vars['total_batches'] = ceil($total / $limit);                          // Total batches to do.

        $lines = array(
            'utility',
            'options',
            'recount_description'
        );

        foreach ($lines as $line) {
            $this->cached_vars['lang_' . $line] = lang($line);
        }

        // --------------------------------------------
        //  Load page
        // --------------------------------------------

        $this->build_crumbs();
        $this->build_right_links();

        $this->cached_vars['current_page'] = $this->view('utilities.html', null, true);
        return $this->ee_cp_view('index.html');
    }
    // END utilities()


    // --------------------------------------------------------------------

    /**
     * Code pack page
     *
     * @access public
     * @param   string  $message    lang line for update message
     * @return  string              html output
     */

    public function code_pack($message = '')
    {
        $this->prep_message($message, true, true);

        // --------------------------------------------
        //  Load vars from code pack lib
        // --------------------------------------------

        $codePack = $this->lib('CodePack');
        $cpl      =& $codePack;

        $cpl->autoSetLang = true;

        $cpt = $cpl->getTemplateDirectoryArray(
            $this->addon_path . 'code_pack/'
        );

        // --------------------------------------------
        //  Start sections
        // --------------------------------------------

        $sections = array();

        $main_section = array();

        // --------------------------------------------
        //  Prefix
        // --------------------------------------------

        $main_section['template_group_prefix'] = array(
            'title'     => lang('template_group_prefix'),
            'desc'      => lang('template_group_prefix_desc'),
            'fields'    => array(
                'prefix' => array(
                    'type'      => 'text',
                    'value'     => $this->lower_name . '_',
                )
            )
        );

        // --------------------------------------------
        //  Templates
        // --------------------------------------------

        $main_section['templates'] = array(
            'title'     => lang('groups_and_templates'),
            'desc'      => lang('groups_and_templates_desc'),
            'fields'    => array(
                'templates' => array(
                    'type'      => 'html',
                    'content'   => $this->view('code_pack_list', compact('cpt')),
                )
            )
        );

        // --------------------------------------------
        //  Compile
        // --------------------------------------------

        $this->cached_vars['sections'][] = $main_section;

        $this->cached_vars['form_url'] = $this->mcp_link(array(
            'method' => 'code_pack_install'
        ));

        $this->cached_vars['box_class'] = 'code_pack_box';

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        // Final view variables we need to render the form
        $this->cached_vars += array(
            'base_url'              => $this->mcp_link(array(
                'method' => 'code_pack_install'
            )),
            'cp_page_title'         => lang('demo_templates') .
                                        '<br /><i>' . lang('demo_description') . '</i>' ,
            'save_btn_text'         => 'install_demo_templates',
            'save_btn_text_working' => 'btn_saving'
        );

        ee('CP/Alert')->makeInline('shared-form')
        ->asIssue()
        ->addToBody(lang('prefix_error'))
        ->cannotClose()
        ->now();

        return $this->mcp_view(array(
            'file'      => 'code_pack_form',
            'highlight' => 'demo_templates',
            'pkg_css'   => array('mcp_defaults'),
            'pkg_js'    => array('code_pack'),
            'crumbs'    => array(
                array(lang('demo_templates'))
            )
        ));
    }
    //END code_pack


    // --------------------------------------------------------------------

    /**
     * Code Pack Install
     *
     * @access public
     * @param   string  $message    lang line for update message
     * @return  string              html output
     */

    public function code_pack_install()
    {
        $prefix = trim((string) ee()->input->get_post('prefix'));

        if ($prefix === '') {
            return ee()->functions->redirect($this->mcp_link(array(
                'method' => 'code_pack'
            )));
        }

        // -------------------------------------
        //  load lib
        // -------------------------------------

        $codePack = $this->lib('CodePack');
        $cpl      =& $codePack;

        $cpl->autoSetLang = true;

        // -------------------------------------
        //  ¡Las Variables en vivo! ¡Que divertido!
        // -------------------------------------

        $variables = array();

        $variables['code_pack_name']    = $this->lower_name . '_code_pack';
        $variables['code_pack_path']    = $this->addon_path . 'code_pack/';
        $variables['prefix']            = $prefix;

        // -------------------------------------
        //  install
        // -------------------------------------

        $return = $cpl->installCodePack($variables);

        //--------------------------------------------
        //  Table
        //--------------------------------------------

        $table = ee('CP/Table', array(
            'sortable'  => false,
            'search'    => false
        ));

        $tableData = array();

        //--------------------------------------------
        //  Errors or regular
        //--------------------------------------------

        if (! empty($return['errors'])) {
            foreach ($return['errors'] as $error) {
                $item = array();

                //  Error
                $item[] = lang('error');

                //  Label
                $item[] = $error['label'];

                //  Field type
                $item[] = str_replace(
                    array(
                        '%conflicting_groups%',
                        '%conflicting_data%',
                        '%conflicting_global_vars%'
                    ),
                    array(
                        implode(", ", $return['conflicting_groups']),
                        implode("<br />", $return['conflicting_global_vars'])
                    ),
                    $error['description']
                );

                $tableData[] = $item;
            }
        } else {
            foreach ($return['success'] as $success) {
                $item = array();

                //  Error
                $item[] = lang('success');

                //  Label
                $item[] = $success['label'];

                //  Field type
                if (isset($success['link'])) {
                    $item[] = array(
                        'content'   => $success['description'],
                        'href'      => $success['link']
                    );
                } else {
                    $item[] = str_replace(
                        array(
                            '%template_count%',
                            '%global_vars%',
                            '%success_link%'
                        ),
                        array(
                            $return['template_count'],
                            implode("<br />", $return['global_vars']),
                            ''
                        ),
                        $success['description']
                    );
                }

                $tableData[] = $item;
            }
        }

        $table->setColumns(array(
            'status',
            'description',
            'details',
        ));

        $table->setData($tableData);

        $table->setNoResultsText('no_results');

        $this->cached_vars['table']     = $table->viewData();

        $this->cached_vars['form_url']  = '';

        //---------------------------------------------
        //  Load Page and set view vars
        //---------------------------------------------

        return $this->mcp_view(array(
            'file'      => 'code_pack_install',
            'highlight' => 'demo_templates',
            'pkg_css'   => array('mcp_defaults'),
            'crumbs'    => array(
                array(lang('demo_templates'))
            )
        ));
    }
    //END code_pack_install
}
// END CLASS Rating_mcp
