<html>
<body>
<div>
<?php

$hidden_fields = array();
if (version_compare(APP_VER, '2.8.0') >= 0) {
  $hidden_fields['CSRF_TOKEN'] = CSRF_TOKEN;
} else {
  $hidden_fields['XID'] = $this->security->generate_xid();
}
$formdata = array();
$formdata['enctype'] = 'multi';
$formdata['hidden_fields'] = $hidden_fields;
$formdata['action'] = $ajax_url.AMP.'ajax_method=upload_file';
$formdata['secure'] = false;
$formdata['class'] = "replace-form";

echo $this->functions->form_declaration($formdata);
?>

<strong><?php echo lang('ci:new_image_file')?></strong><br>
<input name="channel_images_file" type="file" accept="image/*">
<input name="image_id" value="<?php echo $image_id?>" type="hidden">
<input name="curr_temp_image_id" id="curr_temp_image_id" value="<?php echo $temp_img_id?>" type="hidden">
	<input name="field_id" value="<?php echo $field_id?>" type="hidden">


<br><br>
<input type="submit" value="<?php echo lang('ci:actions:replace')?>" class="btn btn-primary" id="uploadfilebtn" onclick="document.getElementById('uploadfilebtn').className = 'btn btn-primary disabled'; ">
<?php echo form_close()?>
</div>


<style type="text/css">
body {text-align:center;}
.btn {
  display: inline-block;
  *display: inline;
  padding: 4px 12px;
  margin-bottom: 0;
  *margin-left: .3em;
  font-size: 14px;
  line-height: 20px;
  color: #333333;
  text-align: center;
  text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
  vertical-align: middle;
  cursor: pointer;
  background-color: #f5f5f5;
  *background-color: #e6e6e6;
  background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
  background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
  background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
  background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
  background-repeat: repeat-x;
  border: 1px solid #cccccc;
  *border: 0;
  border-color: #e6e6e6 #e6e6e6 #bfbfbf;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
  border-bottom-color: #b3b3b3;
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe6e6e6', GradientType=0);
  filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
  *zoom: 1;
  -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
     -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
          box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-primary {
  color: #ffffff;
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  background-color: #006dcc;
  *background-color: #0044cc;
  background-image: -moz-linear-gradient(top, #0088cc, #0044cc);
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#0088cc), to(#0044cc));
  background-image: -webkit-linear-gradient(top, #0088cc, #0044cc);
  background-image: -o-linear-gradient(top, #0088cc, #0044cc);
  background-image: linear-gradient(to bottom, #0088cc, #0044cc);
  background-repeat: repeat-x;
  border-color: #0044cc #0044cc #002a80;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc', endColorstr='#ff0044cc', GradientType=0);
  filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
}

.btn-primary.disabled,
.btn-primary[disabled] {
  color: #ffffff;
  background:#ccc;
}

</style>

</body>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo  ee('channel_images:Helper')->getThemeUrl();?>js/jquery.base64.js"></script>
<script src="<?php echo  ee('channel_images:Helper')->getThemeUrl();?>json2.js"></script>

<script>
	 $(document).ready(function () {
         $(".replace-form").submit(function(e){
             e.preventDefault();

             var form = new FormData($(".replace-form")[0]);
             var temp_img_id = $('#curr_temp_image_id').val();
             $.ajax({
                 url: $(".replace-form").attr('action'),
                 method: "POST",
                 data: form,
                 processData: false,
                 contentType: false,
                 success: function(result){
                     var obj = jQuery.parseJSON(result);
                     var jsonData = $.extend(true,{},obj);

                     jsonData.title = jsonData.title ? $.base64Encode(jsonData.title) : '';
                     jsonData.url_title = jsonData.url_title ? $.base64Encode(jsonData.url_title) : '';
                     jsonData.description = jsonData.description ? $.base64Encode(jsonData.description) : '';
                     jsonData.cifield_1 = jsonData.cifield_1 ? $.base64Encode(jsonData.cifield_1) : '';
                     jsonData.cifield_2 = jsonData.cifield_2 ? $.base64Encode(jsonData.cifield_2) : '';
                     jsonData.cifield_3 = jsonData.cifield_3 ? $.base64Encode(jsonData.cifield_3) : '';
                     jsonData.cifield_4 = jsonData.cifield_4 ? $.base64Encode(jsonData.cifield_4) : '';
                     jsonData.cifield_5 = jsonData.cifield_5 ? $.base64Encode(jsonData.cifield_5) : '';
                     jsonData.cover = jsonData.cover ? $.base64Encode("" + jsonData.cover) : '';



                     var strUrl = obj.small_img_url;
	                  strUrl = strUrl.replace(/&amp;/g, "&");

                     window.parent.$('#'+temp_img_id+' img').attr('src',strUrl);
                     window.parent.$('#'+temp_img_id+' input.image_title').attr('value',obj.title);

                     window.parent.$('#'+temp_img_id).attr('data-filename', obj.filename);
                     window.parent.$('#'+temp_img_id+' .ImgUrl').attr('href', strUrl);
                     window.parent.$('#'+temp_img_id+' .ImgUrl').attr('title', obj.title);
                     window.parent.$('#'+temp_img_id+' img').prop("alt", obj.title);

                     window.parent.$('#'+temp_img_id+' textarea.ImageData').text(JSON.stringify(jsonData));
                     window.parent.jQuery.colorbox.close();

                 },
                 error: function(er){}
             });

		});

    });


</script>
</html>


