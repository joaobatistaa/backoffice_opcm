<?php include('session.php'); ?>
<?php include("public/menubar.php"); ?>
<script src="assets/js/ckeditor/ckeditor.js"></script>

<?php

include('public/fcm.php');

	$qry = "SELECT * FROM tbl_settings where id = '1'";
	$result = mysqli_query($connect, $qry);
	$settings_row = mysqli_fetch_assoc($result);

	if(isset($_POST['submit'])) {

	    $sql_query = "SELECT * FROM tbl_settings WHERE id = '1'";
	    $img_res = mysqli_query($connect, $sql_query);
	    $img_row=  mysqli_fetch_assoc($img_res);

	    $data = array(
	        'app_fcm_key' => $_POST['app_fcm_key'],
            'api_key' => $_POST['api_key'],
	        'privacy_policy' => $_POST['privacy_policy'],
	        'about_us' => $_POST['about_us'],
	        'our_mission' => $_POST['our_mission']
	    );

	    $update_setting = Update('tbl_settings', $data, "WHERE id = '1'");

	    if ($update_setting > 0) {
	        $_SESSION['msg'] = "";
	        header( "Location:settings.php");
	        exit;
	    }
	}

?>


    <section class="content">

        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li class="active">Definições</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

                	<form method="post" enctype="multipart/form-data">
                    <div class="card">
                        <div class="header">
                            <h2>Definições</h2>
                            <div class="header-dropdown m-r--5">
                                <button type="submit" name="submit" class="btn bg-blue waves-effect">Guardar Definições</button>
                            </div>
                                <?php if(isset($_SESSION['msg'])) { ?>
                                    <br><div class='alert alert-info'>Guardado com sucesso...</div>
                                    <?php unset($_SESSION['msg']); } ?>
                        </div>
                        <div class="body">

                        	<div class="row clearfix">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        A tua chave do servidor
                                        <br>
                                        <a href="" data-toggle="modal" data-target="#modal-server-key">Como obter uma chave de servidor FCM?</a>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                	
                                    <div class="form-group">
                                        <div class="form-line">
                                            <div class="font-12">Chave de Servidor FCM</div>
                                            <textarea class="form-control" rows="3" name="app_fcm_key" id="app_fcm_key" required><?php echo $settings_row['app_fcm_key'];?></textarea>
                                            <!-- <label class="form-label">Chave de Servidor FCM</label> -->
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-4">
                                    <div class="form-group">
                                        A tua chave API
                                        <br>
                                        <a href="" data-toggle="modal" data-target="#modal-api-key">Onde que tenho de colocar a minha chave API?</a>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                	
                                    <div class="form-group">
                                        <div class="form-line">
                                            <div class="font-12">Chave API</div>
                                            <input type="text" class="form-control" name="api_key" id="api_key" value="<?php echo $settings_row['api_key'];?>" required>
                                            <!-- <label class="form-label">Chave API</label> -->
                                        </div>
                                        <br>
                                        <a href="change-api-key.php" class="btn bg-blue waves-effect">Editar Chave API</a>
                                    </div>
                                </div>

                                <div class="col-sm-4">
                                    <div class="form-group">
                                        Política de Privacidade
                                        <br>
                                        <i>A política de privacidade irá ser apresentada na aplicação android</i>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    
                                    <div class="form-group">
                                        <div class="form-line">
                                            <div class="font-12">Política de Privacidade</div>
                                            <textarea class="form-control" name="privacy_policy" id="privacy_policy" class="form-control" cols="60" rows="10" required><?php echo $settings_row['privacy_policy'];?></textarea>

                                            <?php if ($ENABLE_RTL_MODE == 'true') { ?>
                                            <script>                             
                                                CKEDITOR.replace( 'privacy_policy' );
                                                CKEDITOR.config.contentsLangDirection = 'rtl';
                                            </script>
                                            <?php } else { ?>
                                            <script>                             
                                                CKEDITOR.replace( 'privacy_policy' );
                                            </script>
                                            <?php } ?>

                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        Sobre Nós
                                        <br>
                                        <i>A página Sobre Nós irá ser apresentada na aplicação</i>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    
                                    <div class="form-group">
                                        <div class="form-line">
                                            <div class="font-12">Sobre Nós</div>
                                            <textarea class="form-control" name="about_us" id="about_us" class="form-control" cols="60" rows="10" required><?php echo $settings_row['about_us'];?></textarea>

                                            <?php if ($ENABLE_RTL_MODE == 'true') { ?>
                                            <script>                             
                                                CKEDITOR.replace( 'about_us' );
                                                CKEDITOR.config.contentsLangDirection = 'rtl';
                                            </script>
                                            <?php } else { ?>
                                            <script>                             
                                                CKEDITOR.replace( 'about_us' );
                                            </script>
                                            <?php } ?>

                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        A Nossa Missão
                                        <br>
                                        <i>A página A Nossa Missão irá ser apresentada na aplicação</i>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    
                                    <div class="form-group">
                                        <div class="form-line">
                                            <div class="font-12">A Nossa Missão</div>
                                            <textarea class="form-control" name="our_mission" id="our_mission" class="form-control" cols="60" rows="10" required><?php echo $settings_row['our_mission'];?></textarea>

                                            <?php if ($ENABLE_RTL_MODE == 'true') { ?>
                                            <script>                             
                                                CKEDITOR.replace( 'our_mission' );
                                                CKEDITOR.config.contentsLangDirection = 'rtl';
                                            </script>
                                            <?php } else { ?>
                                            <script>                             
                                                CKEDITOR.replace( 'our_mission' );
                                            </script>
                                            <?php } ?>

                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    </form>

                </div>
            </div>
            
        </div>

    </section>


<?php include('public/footer.php'); ?>