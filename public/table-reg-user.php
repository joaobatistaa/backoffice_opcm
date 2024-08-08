<?php
	include 'functions.php';
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
			$sql_query = "SELECT id, display_name, user_email, user_status2, imageName FROM wpe4_users ORDER BY id DESC";
		} else {
			$sql_query = "SELECT id, display_name, user_email, user_status2, imageName FROM wpe4_users WHERE display_name LIKE ? ORDER BY id DESC";
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
					$data['id'],
					$data['display_name'],
					$data['user_email'],
					$data['user_status2'],
					$data['imageName']
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
			$sql_query = "SELECT id, display_name, user_email, user_status2, imageName FROM wpe4_users ORDER BY id DESC LIMIT ?, ?";
		} else {
			$sql_query = "SELECT id, display_name, user_email, user_status2, imageName FROM wpe4_users WHERE display_name LIKE ? ORDER BY id DESC LIMIT ?, ?";
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
				$data['id'],
				$data['display_name'],
				$data['user_email'],
				$data['user_status2'],
				$data['imageName']
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
            <li class="active">Utilizadores</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Utilizadores</h2>
                        </div>

                        <div class="body table-responsive">
	                        
	                        <form method="get">
	                        	<div class="col-sm-10">
									<div class="form-group form-float">
										<div class="form-line">
											<input type="text" class="form-control" name="keyword" placeholder="Procurar por nome...">
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
										<th>Nome</th>
										<th>Perfil</th>
										<th>Email</th>
										<th>Estado</th>
									</tr>
								</thead>

								
							</table>

							<div class="col-sm-10">Wopps! Não foram encontrados utilizadores.</div>

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
            <li class="active">Utilizadores</a></li>
        </ol>

       <div class="container-fluid">

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Utilizadores</h2>
                        </div>

                        <div class="body table-responsive">
	                        
	                        <form method="get">
	                        	<div class="col-sm-10">
									<div class="form-group form-float">
										<div class="form-line">
											<input type="text" class="form-control" name="keyword" placeholder="Pesquisar por nome...">
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
										<th>Nome</th>
										<th>Email</th>
										<th>Perfil</th>
										<th>Estado</th>
										<th>Ação</th>
									</tr>
								</thead>

								<?php 
									while ($stmt_paging->fetch()) { ?>
										<tr>
											<td><?php echo $data['display_name'];?></td>
											<td><?php echo $data['user_email'];?></td>
											<td>
                                                <?php
                                                if ($data['imageName'] == NULL) {

                                                    ?>
                                                    <img src="assets/images/ic_user.png" class="rounded-image" height="48px" width="48px"/>
                                                    <?php

                                                } else {

                                                    ?>
                                                    <img src="upload/avatar/<?php echo $data['imageName'];?>" class="rounded-image" height="48px" width="48px"/>

                                                <?php } ?>
                                            </td>
											
											<td>
                                                <?php if ($data['user_status2'] == 1) { ?>
                                                    <span class="label bg-green">Ativado</span>
                                                 <?php } else { ?>
                                                    <span class="label bg-red">Desativado</span>
                                                <?php } ?>
                                            </td>

                                            <td>
                                                <a href="edit-user.php?id=<?php echo $data['id'];?>"><i class="material-icons">mode_edit</i></a>
                                            </td>

										</tr>
								<?php 
									}
								?>
							</table>

							<h4><?php $function->doPages($offset, 'registered-user.php', '', $total_records, $keyword); ?></h4>
							<?php 
								}
							?>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </section>