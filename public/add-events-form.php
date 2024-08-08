<?php 
    include('public/fcm.php');
    require_once("public/thumbnail_images.class.php");
    include_once('functions.php');

    if(isset($_POST['submit'])) {

            $event_image = time().'_'.$_FILES['post_image']['name'];
            $pic2            = $_FILES['post_image']['tmp_name'];
            $tpath2          = 'upload/'.$event_image;
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
            $preenchido = ($_POST['youtube']);
            
            if (!$preenchido) {
            $video = $_POST['youtube'];
            $video_id = 'cda11up';
	        } else { 
	            
            $video = $_POST['youtube'];

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

            'event_title'        => addslashes($_POST['event_title']),
            'event_image'        => $event_image,
            'event_datainicial'         => $_POST['event_datainicial'],
            'event_datafinal'         => $_POST['event_datafinal'],
            'event_description'  => addslashes($_POST['event_description']),
            'event_moreinfo'  => addslashes($_POST['event_moreinfo']),
            'event_localname'  => addslashes($_POST['event_localname']),
            'event_video'         => isset($video) ? $video : '',
            'event_videoid'          => $video_id,
            'size'              => isset($bytes) ? $bytes : ''
            );

        $qry = Insert('tbl_events', $data);

        if (isset($imageNames) && count($imageNames) > 0) {
            global $config;
            $last_id = mysqli_insert_id($config);
            $multi_sql = "INSERT INTO tbl_events_gallery (eid, image_name) VALUE ";
            foreach ($imageNames as $imageName) {
                $multi_sql .= "('$last_id', '$imageName'),";
            }
            $multi_sql = trim($multi_sql, ',');
            mysqli_query($config, $multi_sql);
        }

        $_SESSION['msg'] = "";
        header( "Location:add-events.php");
        exit;

    }
?>

   <section class="content">
   
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li><a href="manage-events.php">Eventos</a></li>
            <li class="active">Adicionar Evento</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

                	<form id="form_validation" method="post" enctype="multipart/form-data">
                    <div class="card">
                        <div class="header">
                            <h2>Adicionar Evento</h2>
                                <?php if(isset($_SESSION['msg'])) { ?>
                                    <br><div class='alert alert-info'>Novo evento adicionada com sucesso...</div>
                                    <?php unset($_SESSION['msg']); } ?>
                        </div>
                        <div class="body">

                        	<div class="row clearfix">
                                
                                <div class="col-sm-5">

                                    <div class="form-group">
                                        <div class="font-12">Título do Evento *</div>
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="event_title" id="event_title" placeholder="Título do Evento" required>
                                        </div>
                                    </div>
                                  	
                                    <div class="form-group">
                                        <div class="font-12">Data de Início *</div>
                                        <div class="form-line">
                                            <input type="text" name="event_datainicial" id="date-format" class="datetimepicker form-control" placeholder="Data de Início" required>
                                        </div>
                                    </div>
               
                                    <div class="form-group">
                                        <div class="font-12">Data de Fim *</div>
                                        <div class="form-line">
                                            <input type="text" name="event_datafinal" id="date-format" class="datetimepicker form-control" placeholder="Data de Fim" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="font-12">Local do Evento *</div>
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="event_localname" id="event_localname" placeholder="Local do Evento" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="font-12">Website do Evento </div>
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="event_moreinfo" id="event_moreinfo" placeholder="Website do Evento">
                                        </div>
                                    </div>
                                    
                                    <div id="youtube">
                                        <div class="form-group">
                                            <div class="font-12">Vídeo do Youtube (URL)</div>
                                            <div class="form-line">
                                                <input type="url" class="form-control" name="youtube" id="youtube" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-7">
                                    <div class="font-12">Descrição *</div>
                                    <div class="form-group">
                                        <textarea class="form-control" name="event_description" id="event_description" class="form-control" cols="60" rows="10" required></textarea>

                                        <?php if ($ENABLE_RTL_MODE == 'true') { ?>
                                        <script>                             
                                            CKEDITOR.replace( 'event_description' );
                                            CKEDITOR.config.contentsLangDirection = 'rtl';
                                        </script>
                                        <?php } else { ?>
                                        <script>                             
                                            CKEDITOR.replace( 'event_description' );
                                        </script>
                                        <?php } ?>
                                    </div>
                                    
                                    <div class="font-12 ex1">Imagem do Evento ( jpg / png ) *</div>
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