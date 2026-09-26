<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ExpressionEngine\Service\Addon\Extension;

/**
 * Store Orders Extension
 *
 * Generates a CSV order file:
 *   - automatically when a customer completes an order (store_order_complete_end)
 *   - when an admin sets the order status to "Azuriraj" (store_order_update_status_end)
 *
 * @package     ExpressionEngine
 * @subpackage  Addons
 * @category    Extension
 * @author      WMD
 * @link        https://wmd.hr
 */
class Store_orders_ext extends Extension
{
    public $settings    = array();
    public $description = 'Store orders';
    public $docs_url    = 'https://wmd.hr';
    public $name        = 'Store orders';
    public $version     = '2.0';

    protected $addon_name = 'store_orders';

    // ------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param mixed $settings  Settings array or empty string if none exist.
     */
    public function __construct($settings = '')
    {
        $this->settings = $settings;
    }

    // ------------------------------------------------------------------

    /**
     * Activate Extension
     *
     * Registers both hooks into exp_extensions.
     *
     * @return void
     */
    public function activate_extension()
    {
        $this->settings = array();

        $hooks = array(
            array(
                'hook'   => 'store_order_complete_end',
                'method' => 'order_complete',
            ),
            array(
                'hook'   => 'store_order_update_status_end',
                'method' => 'order_status_update',
            ),
        );

        foreach ($hooks as $hook)
        {
            ee()->db->insert('extensions', array(
                'class'    => __CLASS__,
                'method'   => $hook['method'],
                'hook'     => $hook['hook'],
                'settings' => serialize($this->settings),
                'priority' => 10,
                'version'  => $this->version,
                'enabled'  => 'y',
            ));
        }
    }

    // ------------------------------------------------------------------

    /**
     * Disable Extension
     *
     * @return void
     */
    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__)->delete('extensions');
    }

    // ------------------------------------------------------------------

    /**
     * Update Extension
     *
     * @param  string $current  Currently installed version.
     * @return mixed  void on update / false if already current
     */
    public function update_extension($current = '')
    {
        if ($current == '' || $current == $this->version)
        {
            return FALSE;
        }

        ee()->db->where('class', __CLASS__)->update('extensions', array('version' => $this->version));
    }

    // ------------------------------------------------------------------

    /**
     * Hook: store_order_complete_end
     *
     * Fires once, automatically, when a customer successfully completes
     * payment and the cart is converted into an order.
     * Always generates the file — no status check required.
     */
    public function order_complete($order)
    {
        if (ee()->extensions->last_call)
        {
            $order = ee()->extensions->last_call;
        }

        $this->write_order_file($order);
    }

    // ------------------------------------------------------------------

    /**
     * Hook: store_order_update_status_end
     *
     * Fires whenever an admin manually changes an order status in the CP.
     * Only regenerates the file when the status is "Azuriraj" (manual correction).
     */
    public function order_status_update($order)
    {
        if (ee()->extensions->last_call)
        {
            $order = ee()->extensions->last_call;
        }

        if ($order->order_status_name === 'Azuriraj')
        {
            $this->write_order_file($order);
        }
    }

    // ------------------------------------------------------------------

    /**
     * Resolve the deepest category ID for a given entry, translate it
     * via old.txt / new.txt, and return the mapped category code string.
     *
     * @param  int    $entry_id
     * @return string translated category code, e.g. "46.90.00"
     */
    private function get_translated_category($entry_id)
    {
        $parent_id  = 0;
        $deepest_id = '';

        // Walk down up to 5 category levels
        for ($level = 1; $level <= 5; $level++)
        {
            $cat = ee()->db
                ->select('cp.cat_id, ca.cat_name, ca.parent_id')
                ->from('exp_category_posts cp')
                ->join('exp_categories ca', 'cp.cat_id = ca.cat_id', 'left')
                ->where('cp.entry_id', $entry_id)
                ->where('ca.parent_id', $parent_id)
                ->where('ca.group_id', 3)
                ->limit(1)
                ->get()
                ->row_array();

            if (empty($cat['cat_id'])) break;

            $deepest_id = $cat['cat_id'];
            $parent_id  = $cat['cat_id'];
        }

        // Wrap deepest ID with && delimiters to match old.txt format
        $category_full       = '&&' . $deepest_id . '&&';
        $translated_category = '46.90.00'; // default fallback

        $old_file = $_SERVER['DOCUMENT_ROOT'] . '/systemxtr/user/addons/store_orders/old.txt';
        $new_file = $_SERVER['DOCUMENT_ROOT'] . '/systemxtr/user/addons/store_orders/new.txt';

        $old_lines = file($old_file, FILE_IGNORE_NEW_LINES);
        $new_lines = file($new_file, FILE_IGNORE_NEW_LINES);

        for ($i = 0; $i < count($old_lines); $i++)
        {
            if (trim($old_lines[$i]) === trim($category_full))
            {
                $translated_category = trim($new_lines[$i]);
                break;
            }
        }

        return $translated_category;
    }

    // ------------------------------------------------------------------

    /**
     * Build and write the CSV order file to /orders/narudzba-{order_id}.csv
     *
     * File format:
     *   D: line — order header / billing details
     *   M: line — shipping address (empty fields if same as billing)
     *   S: lines — one per order item, plus optional handling fee line
     *
     * Encoding: UTF-8 source → CP1250 output (required by the external system)
     *
     * @param object $order  Store order object
     */
    private function write_order_file($order)
    {
        // --- Payment method mapping ---
        $payment_method = $order->payment_method;
        if ($payment_method === 'Check')          $payment_method = 'V';
        if ($payment_method === 'TwoCheckout')    $payment_method = 'K';
        if ($payment_method === 'PayPal_Express') $payment_method = 'P';

        $order_id       = $order->order_id;
        $order_date     = date('d.m.Y', $order->order_date);
        $order_discount = number_format($order->order_discount, 2, '.', '');

        // --- D line: order header / billing ---
        $content_order =
            'D:'
            . $order_id                                                        . ';'
            . $order->billing_first_name                                       . ';'
            . $order->billing_last_name                                        . ';'
            . trim($order->billing_address1 . ' ' . $order->billing_address2) . ';'
            . $order->billing_city                                             . ';'
            . $order->billing_postcode                                         . ';'
            . $order->billing_company                                          . ';'
            . $order->order_custom2                                            . ';'  // OIB
            . $order->order_custom3                                            . ';'  // R1 flag
            . $order_date                                                      . ';'
            . $order_discount                                                  . ';'
            . $order->order_email                                              . ';'
            . $order->billing_phone                                            . ';'
            . $payment_method
            . "\n";

        // --- M line: shipping address ---
        if ($order->shipping_same_as_billing == '0')
        {
            $content_order_dostava =
                'M:'
                . $order_id                                                            . ';'
                . $order->shipping_first_name                                          . ';'
                . $order->shipping_last_name                                           . ';'
                . trim($order->shipping_address1 . ' ' . $order->shipping_address2)   . ';'
                . $order->shipping_city                                                . ';'
                . $order->shipping_postcode                                            . ';'
                . $order->shipping_company                                             . ';'
                . ';'  // OIB — not collected for shipping
                . ';'  // R1
                . ';'  // date
                . ';'  // discount
                . ';'  // email
                . $order->shipping_phone
                . "\n";
        }
        else
        {
            // Shipping same as billing — send empty M line so the external system
            // knows to use the D line address
            $content_order_dostava = 'M:' . $order_id . ";;;;;;;;;;;;;\n";
        }

        // --- S lines: one per order item ---
        $content_items = '';
        $item_count    = 0;

        foreach ($order->items as $item)
        {
            $item_count++;

            $itemtitle = $item->title;
            $itemtitle = str_replace("'", '', $itemtitle);
            $itemtitle = str_replace('"',  '', $itemtitle);
            $itemtitle = str_replace(';',  '', $itemtitle);
            $itemtitle = trim($itemtitle);

            $item_price          = number_format($item->price, 2, '.', '');
            $translated_category = $this->get_translated_category($item->entry_id);

            $content_items .=
                'S:'
                . $item_count              . ';'
                . $item->sku               . ';'
                . $itemtitle               . ';'
                . 'kom'                    . ';'
                . $item->item_qty          . ';'
                . $item_price              . ';'
                . $translated_category
                . "\n";
        }

        // --- Optional handling fee line (shipping method 2 = courier) ---
        if ($order->shipping_method == '2')
        {
            $item_count++;
            $content_items .=
                'S:'
                . $item_count               . ';'
                . 'MANTRO'                  . ';'
                . 'Manipulativni trošak'    . ';'
                . 'kom'                     . ';'
                . '1'                       . ';'
                . number_format($order->shipping, 2, '.', '') . ';'
                . '46.90.00'
                . "\n";
        }

        // --- Assemble, encode, and write ---
        $file_name = 'narudzba-' . $order_id . '.csv';
        $content   = $content_order . $content_order_dostava . $content_items;
        $content   = iconv('UTF-8', 'cp1250//TRANSLIT', $content);

        $dir = $_SERVER['DOCUMENT_ROOT'] . '/orders/';
        $fp  = fopen($dir . $file_name, 'wb');
        fwrite($fp, $content);
        fclose($fp);
    }
}

/* End of file ext.store_orders.php */
/* Location: /system/user/addons/store_orders/ext.store_orders.php */
