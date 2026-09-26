<?php

namespace EEHarbor\Rating\Model;

class Param extends BaseModel
{
    protected static $_primary_key  = 'params_id';
    protected static $_table_name   = 'exp_rating_params';

    protected $params_id;
    protected $date;
    protected $hash;
    protected $data;

    // --------------------------------------------------------------------
    /**
     * Get Params
     *
     * @access  public
     * @param   int     $params_id  params id from form
     * @return  mixed               array of params or boolean false
     */

    public function get_params($params_id)
    {
        $e = $this->fetch()
            ->filter('params_id', $params_id)
            ->first();

        if (! $e) {
            return false;
        }

        $params = @json_decode($e->data, true);

        if (empty($params)) {
            return false;
        }

        $this->cleanup();

        return $params;
    }

    //  END get_params()


    // --------------------------------------------------------------------

    /**
     * insert_params - adds multiple params to stored params
     *
     * @access  public
     * @param   (array)  associative array of params to send
     * @return  insert id or false
     */

    public function insert_params($params = array())
    {
        //  ----------------------------------------
        //  Empty?
        //  ----------------------------------------

        if (! is_array($params)) {
            return false;
        }

        //  ----------------------------------------
        //  Serialize
        //  ----------------------------------------

        $params = json_encode($params);

        $this->cleanup();

        //  ----------------------------------------
        //  Insert
        //  ----------------------------------------

        $e = ee('Model')
            ->make($this->getAddonName() . ':' . $this->getClassName(), array(
            'entry_date'    => ee()->localize->now,
            'data'          => $params
            ))->save();

        //----------------------------------------
        //  Return
        //----------------------------------------

        return $e->params_id;
    }
    //  End insert params


    // --------------------------------------------------------------------

    /**
     * Cleans up any old param
     *
     * @access public
     * @return object this for chaining
     */

    public function cleanup()
    {
        //  ----------------------------------------
        //  Delete excess when older than 2 hours
        //  ----------------------------------------

        $e = $this->fetch()
            ->filter('date', '<', ee()->localize->now - 7200)
            ->all();

        foreach ($e as $d) {
            $d->delete();
        }

        return $this;
    }
}
//END Param
