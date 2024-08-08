<?php
    include_once('includes/config.php');
    // start session
    //session_start();

    // if user click Login button
    if(isset($_POST['btnLogin'])){

        // get username and password
        $username = $_POST['username'];
        $password = $_POST['password'];

        // set time for session timeout
        $currentTime = time() + 25200;
        $expired = 3600;

        // create array variable to handle error
        $error = array();

        // check whether $username is empty or not
        if(empty($username)){
            $error['username'] = "*Preencha todos os campos.";
        }

        // check whether $password is empty or not
        if(empty($password)){
            $error['password'] = "*Preencha todos os campos.";
        }

        // if username and password is not empty, check in database
        if(!empty($username) && !empty($password)){

            // change username to lowercase
            $username = strtolower($username);

            //encript password to sha256
            $password = hash('sha256',$username.$password);

            // get data from user table
            $sql_query = "SELECT * FROM tbl_admin WHERE username = ? AND password = ?";

            $stmt = $connect->stmt_init();
            if($stmt->prepare($sql_query)) {
                // Bind your variables to replace the ?s
                $stmt->bind_param('ss', $username, $password);
                // Execute query
                $stmt->execute();
                /* store result */
                $stmt->store_result();
                $num = $stmt->num_rows;
                // Close statement object
                $stmt->close();
                if($num == 1){
                    $_SESSION['user'] = $username;
                    $_SESSION['timeout'] = $currentTime + $expired;
                    header("location: dashboard.php");
                }else{
                    $error['failed'] = "Nome de utilizador ou Password errados!";
                }
            }

        }
    }
?>

    <div class="login-box ">
        <div class="card">
            <div class="body">
                <form method="POST">
                    <center>
                        <img src="assets/images/ic_launcher.png" width="100px" height="100px">
                        <br>
                        <div class="custom-padding1"> <font size=4 color="#068089"> <b>OPCM - Aplicação Oficial</b></font></div>
                        <div class="custom-padding2 col-pink"><?php echo isset($error['failed']) ? $error['failed'] : '';?></div>
                    </center>
                    
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="material-icons">person</i>
                        </span>
                        <div class="form-line">
                            <input type="text" class="form-control" name="username" placeholder="Nome de Utilizador" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="material-icons">lock</i>
                        </span>
                        <div class="form-line">
                            <input type="password" class="form-control" name="password" placeholder="Palavra-Passe" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3 p-t-6"></div>
                        <div class="col-xs-6">
                            <button class="btn btn-block bg-blue waves-effect" type="submit" name="btnLogin">Iniciar Sessão</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>