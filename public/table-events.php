<?php
	include 'functions.php';
	include 'fcm.php';
?>

<?php 

	$sql_user   = "SELECT COUNT(*) as num FROM tbl_fcm_token";
    $total_user = mysqli_query($connect, $sql_user);
    $total_user = mysqli_fetch_array($total_user);
    $total_user = $total_user['num'];

    if (isset($_GET['send_notification_post'])) {

        $qry = "SELECT * FROM tbl_events WHERE eid = '".$_GET['send_notification_post']."'";
        $result = mysqli_query($connect, $qry);
        $row = mysqli_fetch_assoc($result);

        $pesan = $row['event_title'];
        $id = $row['eid'];
        $link = "";

        $image = 'http://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/upload/'.$row['event_image'];

        $users_sql = "SELECT * FROM tbl_fcm_token";

        $users_result = mysqli_query($connect, $users_sql);
        while($user_row = mysqli_fetch_assoc($users_result)) {

            $msg = $pesan;
            $img = $image;
            $id = $id;
            $link = $link;
            $verifica = 2;

            $data = array("title" => $msg, "image" => $img, "id" => $id, "link" => $link, "verifica" => $verifica);

            echo SEND_FCM_NOTIFICATION($user_row['token'], $data);

        }

        if ($result) {
            $error['push_notification'] = "<div class='alert alert-info'>Notificação enviada para $total_user utilizadores.</div>";
        } else {
            $error['push_notification'] = "<div>Erro ao enviar a notificação.</div>";
        }
    }

?>

	<?php 
		// create object of functions class
		$function = new functions;
		
		// create array variable to store data from database
		$data = array();
		
		if(isset($_GET['keyword'])) {	
			// check value of keyword variable
			$keyword = $function->sanitize($_GET['keyword']);
			$bind_keyword = "%".$keyword."%";
		} else {
			$keyword = "";
			$bind_keyword = $keyword;
		}
			
		if (empty($keyword)) {
			$sql_query = "SELECT eid, event_title, event_image, event_datainicial, event_datafinal, event_localname FROM tbl_events
					ORDER BY eid DESC";
		} else {
			$sql_query = "SELECT eid, event_title, event_image, event_datainicial, event_datafinal, event_localname FROM tbl_events
					WHERE event_title LIKE ? 
					ORDER BY eid DESC";
		}
		
		
		$stmt = $connect->stmt_init();
		if ($stmt->prepare($sql_query)) {	
			// Bind your variables to replace the ?s
			if (!empty($keyword)) {
				$stmt->bind_param('s', $bind_keyword);
			}
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
					$data['event_localname']
					);
			// get total records
			$total_records = $stmt->num_rows;
		}
			
		// check page parameter
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 1;
		}
						
		// number of data that will be display per page		
		$offset = 10;
						
		//lets calculate the LIMIT for SQL, and save it $from
		if ($page) {
			$from 	= ($page * $offset) - $offset;
		} else {
			//if nothing was given in page request, lets load the first page
			$from = 0;	
		}	
		
		if (empty($keyword)) {
			$sql_query = "SELECT eid, event_title, event_image, event_datainicial, event_datafinal, event_localname FROM tbl_events
					ORDER BY eid DESC LIMIT ?, ?";
		} else {
			$sql_query = "SELECT eid, event_title, event_image, event_datainicial, event_datafinal, event_localname FROM tbl_events
					WHERE event_title LIKE ? 
					ORDER BY eid DESC LIMIT ?, ?";
		}
		
		$stmt_paging = $connect->stmt_init();
		if ($stmt_paging ->prepare($sql_query)) {
			// Bind your variables to replace the ?s
			if (empty($keyword)) {
				$stmt_paging ->bind_param('ss', $from, $offset);
			} else {
				$stmt_paging ->bind_param('sss', $bind_keyword, $from, $offset);
			}
			// Execute query
			$stmt_paging ->execute();
			// store result 
			$stmt_paging ->store_result();
			$stmt_paging->bind_result(
				$data['eid'],
				$data['event_title'],
				$data['event_image'],
				$data['event_datainicial'],
				$data['event_datafinal'],
				$data['event_localname']
			);
			// for paging purpose
			$total_records_paging = $total_records; 
		}

		// if no data on database show "No Reservation is Available"
		if ($total_records_paging == 0) {
	
	?>

    <section class="content">

        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li class="active">Eventos</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Eventos</h2>
                            <div class="header-dropdown m-r--5">
                                <a href="add-events.php"><button type="button" class="btn bg-blue waves-effect">Adicionar Evento</button></a>
                            </div>
                        </div>

                        <div class="body table-responsive">
	                        
	                        <form method="get">
	                        	<div class="col-sm-10">
									<div class="form-group form-float">
										<div class="form-line">
											<input type="text" class="form-control" name="keyword" placeholder="Pesquisar por título...">
										</div>
									</div>
								</div>
								<div class="col-sm-2">
					                <button type="submit" name="btnSearch" class="btn bg-blue btn-circle waves-effect waves-circle waves-float"><i class="material-icons">search</i></button>
								</div>
							</form>
										
							<table class='table table-hover table-striped'>
								<thead>
									<tr>
										<th width="40%">Título do Evento</th>
										<th width="10%">Imagem do Evento</th>
										<th width="12%">Data de Início</th>
										<th width="12%">Data de Fim</th>
										<th width="12%">Local do Evento</th>
										<th width="14%"><center>Ação</center></th>
									</tr>
								</thead>

								
							</table>

							<div class="col-sm-10">Wopps! Nenhum evento encontrado.</div>

						</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

	<?php 
		// otherwise, show data
		} else {
			$row_number = $from + 1;
	?>

    <section class="content">

        <ol class="breadcrumb">
            <li><a href="dashboard.php">Painel</a></li>
            <li class="active">Eventos</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Eventos</h2>
                            <div class="header-dropdown m-r--5">
                                <a href="add-events.php"><button type="button" class="btn bg-blue waves-effect">Adicionar Evento</button></a>
                            </div>
                            <br>
                                <?php echo isset($error['push_notification']) ? $error['push_notification'] : '';?>
                        </div>

                        <div class="body table-responsive">
	                        
	                        <form method="get">
	                        	<div class="col-sm-10">
									<div class="form-group form-float">
										<div class="form-line">
											<input type="text" class="form-control" name="keyword" placeholder="Pesquisar por título do evento...">
										</div>
									</div>
								</div>
								<div class="col-sm-2">
					                <button type="submit" name="btnSearch" class="btn bg-blue btn-circle waves-effect waves-circle waves-float"><i class="material-icons">search</i></button>
								</div>
							</form>
										
							<table class='table table-hover table-striped'>
								<thead>
									<tr>
										<th width="40%">Título do Evento</th>
										<th width="10%">Imagem do Evento</th>
										<th width="12%">Data de Início</th>
										<th width="12%">Data de Fim</th>
										<th width="12%">Local</th>
										<th width="14%"><center>Ação</center></th>
									</tr>
								</thead>
                                
                                <?php 
									while ($stmt_paging->fetch()) { ?>
										<tr>
											<td><?php echo $data['event_title'];?></td>

							            	<td>
							            			<img src="upload/<?php echo $data['event_image'];?>" height="48px" width="60px"/>
							            	</td>

											<td>
												<?php 
													$date = strtotime($data['event_datainicial']);
													$new_date = date("F d, Y H:i:s", $date);
													echo $new_date; 
												?>
											</td>
											<td>
												<?php 
													$date = strtotime($data['event_datafinal']);
													$new_date = date("F d, Y H:i:s", $date);
													echo $new_date; 
												?>
											</td>
											<td>
                                                <?php echo $data['event_localname'];?>
											</td>
											<td><center>
												<a href="manage-events.php?send_notification_post=<?php echo $data['eid'];?>" onclick="return confirm('Enviar notificação para todos os utilizadores?')">
                                                <i class="material-icons">notifications_active</i>
                                            	</a>

									            <a href="events-detail.php?id=<?php echo $data['eid'];?>">
									                <i class="material-icons">launch</i>
									            </a>

									            <a href="edit-events.php?id=<?php echo $data['eid'];?>">
									                <i class="material-icons">mode_edit</i>
									            </a>
									                        
									            <a href="delete-events.php?id=<?php echo $data['eid'];?>" onclick="return confirm('Tens a certeza que pretendes eliminar este evento?')" >
									                <i class="material-icons">delete</i>
									            </a></center>
									        </td>
										</tr>
								<?php 
									}
								?>
							</table>

							<h4><?php $function->doPages($offset, 'manage-events.php', '', $total_records, $keyword); ?></h4>
							<?php 
								}
							?>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </section>