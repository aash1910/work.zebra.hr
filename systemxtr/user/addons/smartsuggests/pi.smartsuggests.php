<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Smartsuggests module for ExpressionEngine
 * Copyright (c) 2012 ashraful1910@gmail.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

include(PATH_THIRD.'smartsuggests/config.php');

$plugin_info = array(
	'pi_name'			=> SMARTSUGGESTS_NAME,
	'pi_version'		=> SMARTSUGGESTS_VERSION,
	'pi_author'			=> 'Exp:Ilija_Divic',
	'pi_author_url'		=> SMARTSUGGESTS_DOCS,
	'pi_description'	=> SMARTSUGGESTS_DESCRIPTION,
	'pi_usage'        => Smartsuggests::usage()
);

class Smartsuggests
{
	public $return_data;
	public $EE; // WMD 2026-08-05: PHP 8.2+ dynamic property deprecation

	public function __construct()
	{
		$this->EE = get_instance();

		$this->EE->load->helper('string');

		$this->EE->lang->loadfile('smartsuggests');
	}
	
	
	public function smartsuggest()
	{
		$q = $this->EE->db->escape_like_str($this->EE->input->get('q'));
		
		$final_products = array('header' => array(), 'data' => array());

		$start = microtime(true);
		$query = $this->EE->db->query("SELECT t.title as title,t.url_title as url_title,t.entry_id as entry_id,comment_url,filename,link_entry_id,extension,field_id_18 FROM exp_channel_titles t
		LEFT JOIN exp_channels c on t.channel_id = c.channel_id LEFT JOIN exp_channel_data_field_18 d on t.entry_id = d.entry_id  LEFT JOIN exp_channel_images ci on t.entry_id = ci.entry_id WHERE (t.channel_id = 5) AND (t.status = 'open') AND (ci.image_order IN (0,1)) AND (t.title like '%".$q."%' OR d.field_id_18 LIKE '%".$q."%') GROUP BY t.entry_id ORDER BY entry_date DESC LIMIT 300000");
		$query_time = microtime(true) - $start;
		$count = $query->num_rows();
		$i=0;
		if($count > 0){
			foreach($query->result_array() as $row)
			{
			    if($i++ == 10) break;

				$fileName = $row['filename'];
				$extension = $row['extension'];
				//$fileNameNoExtension = preg_replace("/\.[^.]+$/", "", $fileName);
				$fileNameNoExtension = pathinfo($fileName, PATHINFO_FILENAME);
				$folder = $row['entry_id'];
				$link_entry_id = $row['link_entry_id'];
				if ($link_entry_id != '0'){
				$folder = $link_entry_id;
				}

				$final_products['data'][] = array(
								'primary' => $row['title'],
								'url' => 'https://zebra.hr/shop/cijena/'.$row['url_title'],
								'image' => 'https://zebra.hr/images/uploads/'.$folder.'/'.$fileNameNoExtension.'__shop-cart.'.$extension
							);
			}
		}
		$final_products['header'] = array(
												'title' => '<input type="submit" value="Prikaži sve" />',
												'num' => $count,
												'limit' => 10
											);
		$final_products['query_time'] = round($query_time, 4);

		/* Output JSON */
		$final = array($final_products);
		header('Content-type: application/json');
		header("Cache-Control: no-store, no-cache, must-revalidate");
		echo json_encode($final);
		exit;

    }
	
	
	
	
	public static function usage()
    {
        ob_start();  ?>

The Smartsuggest Plugin simply outputs a
list of 15 products of your site by ajax.
Here ,
U can change number of products , images paths,
channel id of products etc. from smartsuggest() function. 


    {exp:smartsuggests:smartsuggest}

This is an incredibly simple Plugin.


    <?php
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }
	


}

/* End of file pi.smartsuggest.php */
/* Location: ./system/expressionengine/third_party/smartsuggests/pi.smartsuggests.php */