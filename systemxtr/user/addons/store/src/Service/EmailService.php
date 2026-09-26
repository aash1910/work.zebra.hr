<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Service;

use Store\Model\Email;
use Store\Model\Order;

class EmailService extends AbstractService
{
    protected $snippets;

    /**
     * Send an email
     */
    public function send(Email $email, Order $order)
    {
        ee()->load->helper('text');
        ee()->load->library('email');

        ee()->email->clear(TRUE);

        $tag_vars = [$order->toTagArray()];

        ee()->email->to($this->parse($email->to, $tag_vars));
        ee()->email->wordwrap = $email->word_wrap;
        ee()->email->mailtype = $email->mail_format;

        if (ee()->config->item('store_from_email')) {
            ee()->email->from(ee()->config->item('store_from_email'), ee()->config->item('store_from_name'));
        } else {
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        }

        if ($email->bcc) {
            ee()->email->bcc($email->bcc);
        }
        ee()->email->subject($this->parse($email->subject, $tag_vars));
        ee()->email->message($this->parse_html($email->contents, $tag_vars, true));

        //$attachuvjeti = $_SERVER['DOCUMENT_ROOT'] ."/images/img/zebra-hr-uvjeti-koristenja.pdf";
        $attachuvjeti = FCPATH . 'images/img/zebra-hr-uvjeti-koristenja.pdf';
        ee()->email->attach($attachuvjeti);

        ee()->email->send();
    }

    /**
     * Parse a template and return as plain text string (no html entities)
     *
     * @param $template
     * @param $tag_vars
     * @param bool $parse_embeds
     * @return string
     */
    public function parse($template, $tag_vars, $parse_embeds = false)
    {
        return html_entity_decode($this->parse_html($template, $tag_vars, $parse_embeds));
    }

    /**
     * Seriously weak
     *
     * @param $template
     * @param $tag_vars
     * @param bool $parse_embeds
     * @return string
     */
    public function parse_html($template, $tag_vars, $parse_embeds = false)
    {
        ee()->load->library('template', null, 'TMPL');

        if ($parse_embeds) {
            // extra weak
            if (null === $this->snippets) {
                ee()->db->select('snippet_name, snippet_contents');
                ee()->db->where('(site_id = ' . ee()->db->escape_str(ee()->config->item('site_id')) . ' OR site_id = 0)');

                if (ee()->db->get('snippets')->num_rows() > 0) {
                    $this->snippets = [];

                    foreach (ee()->db->get('snippets')->result() as $var) {
                        $snippets[$var->snippet_name] = $var->snippet_contents;
                    }
                }

                if (is_array($this->snippets)) {
                    ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $this->snippets);
                }
            }

            // parse simple variables
            $template = ee()->TMPL->parse_variables($template, $tag_vars);

            // parse as complete template (embeds, snippets, and globals)
            ee()->TMPL->parse($template);
            $template = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
        }

        // parse simple variables
        return ee()->TMPL->parse_variables($template, $tag_vars);
    }
}
