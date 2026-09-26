<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Catdesc_ext
{
    public $name = 'Catdesc';
    public $version = '1.0.0';
    public $description = 'Add RTE/Redactor to Category Description field';

    public function activate_extension()
    {
        $hooks = ['cp_js_end', 'cp_css_end'];

        foreach ($hooks as $hook) {
            ee()->db->insert('extensions', [
                'class'    => __CLASS__,
                'method'   => $hook,
                'hook'     => $hook,
                'settings' => '',
                'priority' => 10,
                'version'  => $this->version,
                'enabled'  => 'y'
            ]);
        }
    }

    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__)->delete('extensions');
    }

//    public function cp_js_end()
//    {
//
//        $output = ee()->extensions->last_call;
//        $output .= "
//    var css = document.createElement('link');
//    css.rel = 'stylesheet';
//    css.href = 'https://work.zebra.hr/js/redactor/redactor.css';
//    document.head.appendChild(css);
//
//    var script = document.createElement('script');
//    script.src = 'https://work.zebra.hr/js/redactor/redactor.min.js';
//
//    script.onload = function () {
//        if (typeof $ === 'undefined' || !$.fn.redactor) return;
//
//        $([name='cat_description']).redactor({
//            buttons: ['format', 'bold', 'italic', 'link', 'unorderedlist', 'orderedlist', 'formatting']
//        });
//    };
//
//    document.head.appendChild(script);
//        ";
//
//        return $output;
//
//    } // END cp_js_end()

public function cp_js_end()
{
    $output = ee()->extensions->last_call;
    $output .= "
        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://work.zebra.hr/js/quill/quill.snow.css';
        document.head.appendChild(css);

        var script = document.createElement('script');
        script.src = 'https://work.zebra.hr/js/quill/quill.js';

        script.onload = function() {
            var textarea = document.querySelector('textarea[name=\"cat_description\"]');
            if (!textarea) return;

            var container = document.createElement('div');
            container.innerHTML = textarea.value;
            textarea.parentNode.insertBefore(container, textarea);
            textarea.style.display = 'none';

            var quill = new Quill(container, {
                theme: 'snow',
                modules: {
                    toolbar: ['bold', 'italic', 'link', { list: 'ordered' }, { list: 'bullet' }, { header: [1, 2, 3, false] }]
                }
            });

            var form = textarea.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    textarea.value = quill.root.innerHTML;
                });
            }
        };

        document.head.appendChild(script);
    ";

    return $output;
}

    public function cp_css_end()
    {
        //return '.field-control { display: none !important; }';
        return "";
    }
}