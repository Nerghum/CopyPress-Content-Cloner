<div class="wrap">
    <h2>CopyPress Content Cloner Settings</h2>
    <hr>
</div>

<!-- Setting page HTML -->
<div class="CPC-tab">
    <button class="tablinks active">All Post</button>
</div>

<div id="allPost" class="CPC-tabcontent">
    <h3 class="">Import All Post (99 max)</h3>
    <p><input type="url" name="url" id="url" style="width: 350px;" value="" placeholder="URL">
        <input type="button" class="button button-primary" value="Import" onclick="CPC_importPost()"></p>
    <div id="result">

    </div>
</div>

<div class="CPC-premium">
    <a href="https://nerghum.com/contact/?buy" class="CPC-buyNow">Buy Premium</a><br>
    <img src="<?php echo plugin_dir_url(__FILE__)?>/premium.png" style="max-width: 100%;width: 701px;">
</div>

<script>

// import json from given wordpress website with rest api
function CPC_importPost() {
 var url = document.getElementById('url').value;
 
    jQuery.ajax({
        type: "get",
        dataType: "json",
        url: url + '/wp-json/wp/v2/posts/?per_page=99',
        success: function (response) {
            document.getElementById("result").innerHTML += response.length +" Post Processing... <br>";
            for(var limite = 0; limite < response.length; limite++ ){
                CPC_process_data(response[limite]);
            }
        }
    });
}

// Process the Requested JSON data from the website link
function CPC_process_data(data){
var url = document.getElementById('url').value;
    var title;
    var content;
    var imageUrl;
    var categories = [];

    title = data.title.rendered;
    content = data.content.rendered;

    // Categories
    var categoryRequests = data.categories.map(function (categoryId) {
        return jQuery.ajax({
            type: "get",
            dataType: "json",
            url: url + '/wp-json/wp/v2/categories/' + categoryId,
            success: function (response) {
                categories.push(response.name);
            }
        });
    });

    // Image URL
    var imageUrlRequest = jQuery.ajax({
        type: "get",
        dataType: "json",
        url: url + '/wp-json/wp/v2/media/' + data.featured_media,
        success: function (response) {
            imageUrl = response.source_url;
        }
    });

    // Wait for all category requests and the image URL request to complete
    jQuery.when.apply(jQuery, categoryRequests.concat(imageUrlRequest)).done(function () {
        CPC_newPost(title, content, imageUrl, categories );
    });
}

// AJAX new post create function
function CPC_newPost(title, content, imageUrl, categories){
    jQuery.ajax({
    type: "POST",
    dataType: "html",
    data:{
        action: 'CPC_import_new_post',
        title:title,
        content:content,
        imageUrl:imageUrl,
        categories:categories 
    },
    url: "<?php echo admin_url('admin-ajax.php'); ?>",
    success: function(response){
        document.getElementById("result").innerHTML += response + " <br>";
    }
    });
}
</script>

<style>

/* Style the tab */
.CPC-tab {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: #f1f1f1;
}

/* Style the buttons inside the tab */
.CPC-tab button {
  background-color: inherit;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.CPC-tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.CPC-tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.CPC-tabcontent {
  display: block;
  padding: 6px 12px;
  border: 1px solid #ccc;
  border-top: none;
}

.CPC-buyNow {
  text-decoration: none;
  font-weight: bold;
  display: block;
  margin-top: 20px;
  width: 300px;
  background-color: #13aa52;
  border: 1px solid #13aa52;
  border-radius: 4px;
  box-shadow: rgba(0, 0, 0, .1) 0 2px 4px 0;
  box-sizing: border-box;
  color: #fff;
  cursor: pointer;
  font-family: "Akzidenz Grotesk BQ Medium", -apple-system, BlinkMacSystemFont, sans-serif;
  font-size: 16px;
  font-weight: 400;
  outline: none;
  outline: 0;
  padding: 10px 25px;
  text-align: center;
  transform: translateY(0);
  transition: transform 150ms, box-shadow 150ms;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
}

.CPC-buyNow:hover {
  box-shadow: rgba(0, 0, 0, .15) 0 3px 9px 0;
  transform: translateY(-2px);
  color: #fff;
}
</style>
