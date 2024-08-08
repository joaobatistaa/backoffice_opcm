<?php include 'functions.php' ?>

<?php

	if (isset($_GET['id'])) {
		$id = $_GET['id'];
	} else {
		$id = "";
	}
			
	// create array variable to handle error
	$error = array();
	
	// create array variable to store data from database
	$data = array();
			
	if (isset($_POST['btnSave'])) {
		$process = $_POST['user_status2'];
		$sql_query = "UPDATE wpe4_users SET user_status2 = ? WHERE id = ?";
			
		$stmt = $connect->stmt_init();
		if ($stmt->prepare($sql_query)) {	
			// Bind your variables to replace the ?s
			$stmt->bind_param('ss', $process, $id);
			// Execute query
			$stmt->execute();
			// store result 
			$update_result = $stmt->store_result();
			$stmt->close();
		}
			
		// check update result
		if ($update_result) {
			//$error['update_data'] = "<br><div class='alert alert-info'>Estado do Utilizador editado com sucesso...</div>";
			$succes =<<<EOF
				<script>
				alert('Estado do Utilizador editado com sucesso...');
				window.location = 'registered-user.php';
				</script>
EOF;

			echo $succes;
		} else {
			$error['update_data'] = "<br><div class='alert alert-danger'>Erro ao editar o estado do utilizador</div>";
		}
	}
		
	// get data from reservation table
	$sql_query = "SELECT * FROM wpe4_users WHERE id = ?";
		
	$stmt = $connect->stmt_init();
	if ($stmt->prepare($sql_query)) {	
		// Bind your variables to replace the ?s
		$stmt->bind_param('s', $id);
		// Execute query
		$stmt->execute();
		// store result 
		$stmt->store_result();
		$stmt->bind_result($data['id'], 
				$data['user_type'],
				$data['user_login'],
				$data['user_email'],
				$data['user_pass'],
				$data['user_status2'], 
				$data['imageName']
				);
		$stmt->fetch();
		$stmt->close();
	}
		
?>

    <section class="content">

        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li><a href="registered-user.php">Utilizadores</a></li>
            <li class="active">Editar Utilizador</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

                    <form id="form_validation" method="post" enctype="multipart/form-data">
                    <div class="card">
                        <div class="header">
                            <h2>Editar Utilizador</h2>
                                <?php echo isset($error['update_data']) ? $error['update_data'] : ''; ?>
                        </div>
                        <div class="body">

                            <div class="row clearfix">
                                
                                <div class="col-sm-12">

                    <div class="form-group">
                        <div class="font-12">Estado</div>

                    <select class="form-control show-tick" name="user_status2" id="user_status2">	
						<?php if ($data['user_status2'] == 1) { ?>
							<option value="1" selected="selected">Ativado</option>
							<option value="0" >Desativado</option>
						<?php } else { ?>
							<option value="1" >Ativado</option>
							<option value="0" selected="selected">Desativado</option>
						<?php } ?>
					</select>
					</div>

                                    <div class="col-sm-12">
                                         <button class="btn bg-blue waves-effect pull-right" type="submit" name="btnSave">Editar</button>
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