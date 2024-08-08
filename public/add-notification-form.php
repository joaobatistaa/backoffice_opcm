<?php include 'functions.php'; ?>

<?php

	if (isset($_POST['btnAdd'])) {

		$message = $_POST['message'];
		$link	 = $_POST['link'];
				
		// create array variable to handle error
		$error = array();
			
		if (empty($message)) {
			$error['message'] = " <span class='label label-danger'>Must Insert!</span>";
		}
			
		if (!empty($message)) {		

			// insert new data to menu table
			$sql_query = "INSERT INTO tbl_fcm_template (message, link) VALUES (?, ?)";
					
			$stmt = $connect->stmt_init();
			if ($stmt->prepare($sql_query)) {	
				// Bind your variables to replace the ?s
				$stmt->bind_param('ss', $message, $link);
				// Execute query
				$stmt->execute();
				// store result 
				$result = $stmt->store_result();
				$stmt->close();
			}

			if($result) {
		        $succes =<<<EOF
					<script>
					alert('Novo template de notificação adicionado com sucesso...');
					window.location = 'push-notification.php';
					</script>
EOF;
				echo $succes;
		    } else {
		        $error['add_notification'] = "<br><div class='alert alert-danger'>Added Failed</div>";
		    }
		}
	}

?>

    <section class="content">

        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li><a href="push-notification.php">Notificação</a></li>
            <li class="active">Adicionar Novo Template de Notificação</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

                	<form id="form_validation" method="post" enctype="multipart/form-data">
                    <div class="card">
                        <div class="header">
                            <h2>Adicionar Template de Notificação</h2>
                                <?php echo isset($error['add_notification']) ? $error['add_notification'] : '';?>
                        </div>
                        <div class="body">

                        	<div class="row clearfix">
                                
                                <div>
                                    <div class="form-group form-float col-sm-12">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="message" id="message" required>
                                            <label class="form-label">Mensagem</label>
                                        </div>
                                    </div>

                                    <div class="form-group form-float col-sm-12">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="link" id="link" >
                                            <label class="form-label">URL (Opcional</label>
                                        </div>
                                    </div>

                                    <div class="col-sm-12">
                                         <button class="btn bg-blue waves-effect pull-right" type="submit" name="btnAdd">Adicionar</button>
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