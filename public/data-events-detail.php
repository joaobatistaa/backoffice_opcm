<?php 
		if(isset($_GET['id'])) {
			$ID = $_GET['id'];
		}else{
			$ID = "";
		}
			
		// create array variable to handle error
		$error = array();
			
		// create array variable to store data from database
		$data = array();
		
		// get data from reservation table
		$sql_query = "SELECT * FROM tbl_events WHERE eid = ?";
		
		$stmt = $connect->stmt_init();
		if($stmt->prepare($sql_query)) {	
			// Bind your variables to replace the ?s
			$stmt->bind_param('s', $ID);
			// Execute query
			$stmt->execute();
			// store result 
			$stmt->store_result();
			$stmt->bind_result(
					$data['eid'], 
					$data['event_title'],
					$data['event_image'],
					$data['event_datainicial'],
					$data['event_datafinal'],
					$data['event_description'],
					$data['event_moreinfo'],
					$data['event_localname'],
					$data['event_video'],
					$data['event_videoid'],
					$data['event_pessoas'],
					$data['size']
					);
			$stmt->fetch();
			$stmt->close();
		}

			
	?>

	<section class="content">

        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li><a href="manage-news.php">Eventos</a></li>
            <li class="active">Detalhes do Evento</a></li>
        </ol>

        <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                	<form method="post">
                	<div class="card">
                        <div class="header">
                            <h2>Detalhes do Evento</h2>
                        </div>
                        <div class="body">

                        	<div class="row clearfix">
                        	<div class="form-group form-float col-sm-12">
                        		<p>
									<h4>
										<?php echo $data['event_title']; ?>
										<a href="edit-events.php?id=<?php echo $data['eid'];?>"><i class="material-icons">mode_edit</i></a>
										<a href="delete-events.php?id=<?php echo $data['eid'];?>" onclick="return confirm('Tens a certeza que pretendes eliminar este evento?')" ><i class="material-icons">delete</i></a>
									</h4>
								</p>
								
								<p><b>Data de Início:</b> <?php echo $data['event_datainicial']; ?> </p>
                                <p><b>Data de Fim:</b> <?php echo $data['event_datafinal']; ?> </p>
                                <p><b>Local do Evento:</b> <?php echo $data['event_localname']; ?> </p>
                                <p><b>Website do Evento:</b> <?php echo $data['event_moreinfo']; ?> </p>
                                <p><b>Vídeo do Evento:</b> <?php echo $data['event_video']; ?> </p>
                                 
					            <p><img style="max-width:40%" src="upload/<?php echo $data['event_image']; ?>" ></p>
                                
                                <p><b>Descrição do Evento:</b></p>
								<p><?php echo $data['event_description']; ?></p>
								
                	</form>

							<p><b>Total de pessoas que vão a este evento:</b> ( <?php echo $data['event_pessoas']; ?> )</p>
							
							</div>
                        	</div>
                        </div>
                    </div>

                </div>

            </div>
            
        </div>

    </section>