<?php 
    include('public/fcm.php');
    require_once("public/thumbnail_images.class.php");
    include_once('functions.php');

    if(isset($_POST['submit'])) {

        $video_id = 'cda11up';

        if($_POST['upload_type'] == 'Upload') {

            $news_image = time().'_'.$_FILES['news_image']['name'];
            $pic2            = $_FILES['news_image']['tmp_name'];
            $tpath2          = 'upload/'.$news_image;
            copy($pic2, $tpath2);

            $video  = time().'_'.$_FILES['video']['name'];
            $pic1   = $_FILES['video']['tmp_name'];
            $tpath1 ='upload/video/'.$video;
            copy($pic1, $tpath1);
            $bytes = $_FILES['video']['size'];

            if ($bytes >= 1073741824) {
                $bytes = number_format($bytes / 1073741824, 2) . ' GB';
            }

            else if ($bytes >= 1048576) {
                $bytes = number_format($bytes / 1048576, 2) . ' MB';
            }

            else if ($bytes >= 1024) {
                $bytes = number_format($bytes / 1024, 2) . ' KB';
            }

            else if ($bytes > 1) {
                $bytes = $bytes . ' bytes';
            }

            else if ($bytes == 1) {
                $bytes = $bytes . ' byte';
            } else {
                $bytes = '0 bytes';
            }


        } else if ($_POST['upload_type'] == 'Url') {

            $video = $_POST['url_source'];

            $news_image = time().'_'.$_FILES['image']['name'];
            $pic2            = $_FILES['image']['tmp_name'];
            $tpath2          = 'upload/'.$news_image;
            copy($pic2, $tpath2);

        } else if ($_POST['upload_type'] == 'Post') {

            $news_image = time().'_'.$_FILES['post_image']['name'];
            $pic2            = $_FILES['post_image']['tmp_name'];
            $tpath2          = 'upload/'.$news_image;
            copy($pic2, $tpath2);

            /**
             * multiple upload
             */
            $imageNames  = array();
            $imageFiles = functions::reArrayFiles($_FILES['imageoption']);


            foreach ($imageFiles as $imageFile) {
                if ($imageFile['error'] == 0) {
                    $newName = time() . '_' . $imageFile['name'];
                    $img     = $imageFile['tmp_name'];
                    $imgPath = 'upload/' . $newName;
                    copy($img, $imgPath);

                    $imageNames[] = $newName;
                }
            }


        } else {
            $video = $_POST['youtube'];
            $news_image = '';

            function youtube_id_from_url($url) {

                $pattern =
                '%^# Match any youtube URL
                (?:https?://)?  # Optional scheme. Either http or https
                (?:www\.)?      # Optional www subdomain
                (?:             # Group host alternatives
                  youtu\.be/    # Either youtu.be,
                | youtube\.com  # or youtube.com
                  (?:           # Group path alternatives
                    /embed/     # Either /embed/
                  | /v/         # or /v/
                  | /watch\?v=  # or /watch\?v=
                  )             # End path alternatives.
                )               # End host alternatives.
                ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
                $%x'
                ;

                $result = preg_match($pattern, $url, $matches);

                if (false !== $result) {
                    return $matches[1];
                }
                return false;

            }

            $video_id = youtube_id_from_url($_POST['youtube']);

        }

        $data = array(

            'cat_id'            => $_POST['cat_id'],
            'news_title'        => addslashes($_POST['news_title']),
            'video_url'         => isset($video) ? $video : '',
            'video_id'          => $video_id,
            'news_image'        => $news_image,
            'news_date'         => $_POST['news_date'],
            'news_description'  => addslashes($_POST['news_description']),
            'content_type'      => $_POST['upload_type'],
            'size'              => isset($bytes) ? $bytes : ''
            );

        $qry = Insert('tbl_news', $data);

        if (isset($imageNames) && count($imageNames) > 0) {
            global $config;
            $last_id = mysqli_insert_id($config);
            $multi_sql = "INSERT INTO tbl_news_gallery (nid, image_name) VALUE ";
            foreach ($imageNames as $imageName) {
                $multi_sql .= "('$last_id', '$imageName'),";
            }
            $multi_sql = trim($multi_sql, ',');
            mysqli_query($config, $multi_sql);
        }

        $_SESSION['msg'] = "";
        header( "Location:add-news.php");
        exit;

    }

    $sql_category = "SELECT * FROM tbl_category ORDER BY cid DESC";
    $category_result = mysqli_query($connect, $sql_category);
?>

<script type="text/javascript">

    $(document).ready(function(e) {

        $("#upload_type").change(function() {
            var type = $("#upload_type").val();

                if (type == "youtube") {
                    $("#video_upload").hide();
                    $("#video_post").hide();
                    $("#direct_url").hide();
                    $("#youtube").show();
                }

                if (type == "Post") {
                    $("#youtube").hide();
                    $("#video_upload").hide();
                    $("#direct_url").hide();
                    $("#video_post").show();

                    $("#multiple_images").hide();
                }

                if (type == "Url") {
                    $("#youtube").hide();
                    $("#video_upload").hide();
                    $("#video_post").hide();
                    $("#direct_url").show();
                }

                if (type == "Upload") {
                    $("#youtube").hide();
                    $("#video_post").hide();
                    $("#direct_url").hide();
                    $("#video_upload").show();
                }                       
        });

        $( window ).load(function() {
        var type=$("#upload_type").val();

            if (type == "youtube")  {
                $("#video_upload").hide();
                $("#direct_url").hide();
                $("#video_post").hide();
                $("#youtube").show();
            }

            if (type == "Url") {
                $("#youtube").hide();
                $("#video_upload").hide();
                $("#video_post").hide();
                $("#direct_url").show();
            }

            if (type == "Upload") {
                $("#youtube").hide();
                $("#direct_url").hide();
                $("#video_post").hide();
                $("#video_upload").show();
            }

            if (type == "Post") {
                $("#youtube").hide();
                $("#direct_url").hide();
                $("#video_upload").hide();
                $("#video_post").show();

                $("#multiple_images").hide();
            }

        });

    });

</script>

   <section class="content">
   
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li><a href="manage-news.php">Notícias</a></li>
            <li class="active">Adicionar Notícia</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

                	<form id="form_validation" method="post" enctype="multipart/form-data">
                    <div class="card">
                        <div class="header">
                            <h2>Adicionar Notícia</h2>
                                <?php if(isset($_SESSION['msg'])) { ?>
                                    <br><div class='alert alert-info'>Nova notícia adicionada com sucesso...</div>
                                    <?php unset($_SESSION['msg']); } ?>
                        </div>
                        <div class="body">

                        	<div class="row clearfix">
                                
                                <div class="col-sm-5">

                                    <div class="form-group">
                                        <div class="font-12">Título da Notícia *</div>
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="news_title" id="news_title" placeholder="Título da Notícia" required>
                                        </div>
                                    </div>
                                  	
                                    <div class="form-group">
                                        <div class="font-12">Data da Notícia *</div>
                                        <div class="form-line">
                                            <input type="text" name="news_date" id="date-format" class="datetimepicker form-control" placeholder="Data da Notícia" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="font-12">Categoria *</div>
                                        <select class="form-control show-tick" name="cat_id" id="cat_id">
                                            <?php while ($data = mysqli_fetch_array ($category_result)) { ?>
                                            <option value="<?php echo $data['cid'];?>"><?php echo $data['category_name'];?></option>
                                                <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <div class="font-12">Tipo de Conteúdo *</div>
                                        <select class="form-control show-tick" name="upload_type" id="upload_type">
                                                <option value="Post">Notícia Normal</option>
                                                <option value="youtube">Video do YouTube</option>
                                                <option value="Url">Vídeo por URL</option>
                                                <option value="Upload">Carregar Vídeo</option>
                                        </select>
                                    </div>

                                    <div id="video_post">
                                        <div class="font-12 ex1">Imagem da Notícia ( jpg / png ) *</div>
                                        <div class="form-group">
                                            <input type="file" name="post_image" id="post_image" class="dropify-image" data-max-file-size="1M" data-allowed-file-extensions="jpg jpeg png gif" required />
                                            <!-- <input type="file" name="post_image" id="post_image" required /> -->
                                        </div>

                                        <div id="multiple_images">
                                            <!-- <div class="font-12 ex1">Image Optional ( jpg / png )</div> -->
                                            <div class="form-group">
                                                <input type="hidden" name="imageoption[]" id="imageoptions"/>
                                            </div>
                                            <div class="multiupload"></div>
                                            <input type="hidden" class="btn bg-blue waves-effect" id="addnewUpload" value="add more" />
                                        </div>

                                    </div>
                                    
                                    <div id="youtube">
                                        <div class="form-group">
                                            <div class="font-12">Youtube URL</div>
                                            <div class="form-line">
                                                <input type="url" class="form-control" name="youtube" id="youtube" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="direct_url">
                                        <div class="form-group">
                                            <input type="file" name="image" id="image" class="dropify-image" data-max-file-size="1M" data-allowed-file-extensions="jpg png gif" />
                                        </div>
                                        <div class="form-group">
                                            <div class="font-12">Vídeo URL</div>
                                            <div class="form-line">
                                                <input type="url" class="form-control" name="url_source" id="url_source" placeholder="http://www.xyz.com/news_title.mp4" required/>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="video_upload">
                                        <div class="form-group">
                                            <input type="file" id="news_image" name="news_image" id="news_image" class="dropify-image" data-max-file-size="1M" data-allowed-file-extensions="jpg png gif" required />
                                        </div>

                                        <div class="form-group">
                                            <input type="file" id="video" name="video" id="video" class="dropify-video" data-allowed-file-extensions="3gp mp4 mpg wmv mkv m4v mov flv" required/>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-sm-7">
                                    <div class="font-12">Descrição *</div>
                                    <div class="form-group">
                                        <textarea class="form-control" name="news_description" id="news_description" class="form-control" cols="60" rows="10" required></textarea>

                                        <?php if ($ENABLE_RTL_MODE == 'true') { ?>
                                        <script>                             
                                            CKEDITOR.replace( 'news_description' );
                                            CKEDITOR.config.contentsLangDirection = 'rtl';
                                        </script>
                                        <?php } else { ?>
                                        <script>                             
                                            CKEDITOR.replace( 'news_description' );
                                        </script>
                                        <?php } ?>
                                    </div>

                                    <button type="submit" name="submit" class="btn bg-blue waves-effect pull-right">PUBLICAR</button>
                                    
                                </div>

                            </div>
                        </div>
                    </div>
                    </form>

                </div>
            </div>
            
        </div>

    </section>