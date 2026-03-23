<?php

include 'config.php';

session_start();

error_reporting(0);

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $encrypted_password = md5($password); // Encrypt the password using MD5

    $sql = "SELECT * FROM thanhvien WHERE TV_MA = ? AND TV_MATKHAU = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $encrypted_password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['username'] = $row['TV_TEN'];
        $_SESSION['code'] = $row['TV_MA'];
        $_SESSION['active'] = $row['active'];
        $_SESSION['nnd_ma'] = $row['NND_MA']; // <-- dòng mới thêm
        header("Location: capquanly/index.php");
        exit();
    } else {
        echo "<script>alert('Xin lỗi. Mật khẩu hoặc tên đăng nhập không đúng.')</script>";
    }

    $stmt->close();
    $conn->close();
}

?>


<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/quanlycongviec/favicon.ico">
    <title>Đăng Nhập</title>
    <style>
        <style>.content {
            max-width: 800px;
            margin: auto;
        }

        .item {
            background: url("style/old-web_bg-5.png") bottom right no-repeat #2A53A2;

        }

        .item1 {
            background-color: white;
            border-radius: 20px;
            border-radius: 20px;
            text-align: center;
            padding: 40px;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        }

        .btn-primary {
            color: #fff;
            background-color: #2A53A2;
            padding: 12px 30px;
            display: inline-block;
            border-radius: 12px;
            font-weight: 500;
            text-transform: uppercase;
            transition: all .3s;

        }

        .input_tb {
            padding: 10px;
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>

<body class="item">
    <form name="frmMain" method="post" action="" autocomplete="off">
        <table width="800" cellpadding="1" cellspacing="0" align="center" height="90%">
            <tr>
                <td>
                    <div class="item1">
                        <h1>ĐĂNG NHẬP HỆ THỐNG </br></h1>
                        <img src="style/line.jpg" width="100px" /></br>
                        <table align="center">
                            <tr>
                                <td align="right" class="input_tb">Mã Cán Bộ:</td>
                                <td class="input_tb"><input name="name" type="text" size="20" value="" autofocus
                                        style="padding: 5px;" /></td>
                            </tr>
                            <tr>
                                <td align="right" class="input_tb">Mật khẩu:</td>
                                <td class="input_tb"><input name="password" type="password" size="20" value=""
                                        style="padding: 5px;" /></td>
                            </tr>
                        </table>
                        <input type="submit" value="Đăng nhập" align="center" name="submit"
                            class="btn-primary" />
                    </div>
                </td>
            </tr>
        </table>
    </form>
</body>

