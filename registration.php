<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>User Registration</title>
</head>
    <body>
        <?php
            require('database.php');

            if (isset($_REQUEST['username'])){
                $username = stripslashes($_REQUEST['username']);
                $username = mysqli_real_escape_string($con,$username);
                $password = stripslashes($_REQUEST['password']);
                $password = mysqli_real_escape_string($con,$password);
                $reg_date = date("Y-m-d H:i:s");

            $query = "INSERT into `employeelogin` (username, password, reg_date)
            VALUES ('$username', '".md5($password)."','$reg_date')";
            
            $result = mysqli_query($con,$query);

            if($result){
            echo "<div class='form'>
            <h3>You are registered successfully.</h3>";
            }
            }else{
        ?>
            <div class="form">
            <h1>User Registration</h1>
                <form name="registration" action="" method="post">
                    <input type="text" name="username" placeholder="Username" required /><br>
                    <input type="password" name="password" placeholder="Password" required /><br>
                    <input type="submit" name="submit" value="Register" />
                </form>
            </div>
        <?php } ?>
        <script src="script.js"></script>
    </body>
</html>