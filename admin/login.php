<?php
session_start();
require_once "../master/connect.php";

if (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true) {
  header("Location:index.php");
  exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $pin = $_POST['pin'] ?? '';
    
  if ($pin == $master_user__id) {
    $_SESSION['admin_logged_in'] = true;
    header("Location:index.php");
    exit();
  } else {
    $error = "الرقم السري غير صحيح";
  }
}
?>
<html lang="ar" dir="rtl" type="text/html">
 <head>
  <!--meta-->
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!--style-->
   <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            direction: ltr;
            text-align: center;
            letter-spacing: 5px;
        }
        
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fcc;
        }
        
        .icon {
            text-align: center;
            margin-bottom: 20px;
            font-size: 50px;
        }
   </style>
  <!--title-->
   <title>تسجيل الدخول - لوحة التحكم</title>
 </head>
 <body>
  <div class="login-container">
   <div class="icon">🔐</div>
    <h1>لوحة التحكم</h1>
    <?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
     <div class="form-group">
      <label for="pin">الرقم السري</label>
      <input type="password" id="pin" name="pin" required autofocus autocomplete="off" value="<?= $_COOKIE["id"] ?? ""; ?>">
     </div>
     <button type="submit">دخول</button>
    </form>
  </div>
 </body>
</html>