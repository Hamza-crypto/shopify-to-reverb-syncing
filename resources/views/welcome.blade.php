<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            background: #f1f1f1;
            color: #444;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        #login {
            background: #fff;
            border-radius: 3px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin: 100px auto;
            max-width: 360px;
            overflow: hidden;
            padding: 1em;
        }

        h1 a {
            background-image: url("https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/WordPress_logo.svg/1280px-WordPress_logo.svg.png");
            background-position: center top;
            background-repeat: no-repeat;
            background-size: 80%;
            color: #007cba;
            display: block;
            font-family: 'Open Sans', sans-serif;
            font-size: 24px;
            font-weight: 600;
            height: 84px;
            line-height: 1.3em;
            margin: 0 auto 20px;
            padding: 0;
            text-decoration: none;
            text-indent: -9999px;
            width: 80%;
        }

        #loginform {
            margin-bottom: 0;
        }

        #loginform p {
            margin-bottom: 20px;
        }

        #loginform p:last-child {
            margin-bottom: 0;
        }

        #loginform input[type="text"],
        #loginform input[type="password"] {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
            color: #555;
            font-size: 14px;
            line-height: 1.3em;
            padding: 10px;
            width: 100%;
        }

        .login__rememberme {
            margin-bottom: 10px;
        }

        #loginform input[type="submit"] {
            background-color: #007cba;
            border: none;
            border-radius: 3px;
            box-shadow: none;
            color: #fff;
            cursor: pointer;
            display: block;
            font-size: 16px;
            font-weight: 600;
            height: 40px;
            line-height: 1.3em;
            margin-top: 10px;
            padding: 10px;
            width: 100%;
        }

        #loginform input[type="submit"]:hover {
            background-color: #005b8e;
        }

        #nav a,
        #backtoblog a {
            color: #555;
            text-decoration: none;
        }

        #nav a:hover,
        #backtoblog a:hover {
            color: #007cba;
        }

    </style>
</head>
<body class="login login-action-login wp-core-ui  locale-en-us">
<div id="login">
    <h1><a href="https://wordpress.org/" title="Powered by WordPress" tabindex="-1">Powered by WordPress</a></h1>
    <form name="loginform" id="loginform" action="admin-ajax.php" method="post">
        <p>
            <label for="user_login">Username or Email Address<br>
                <input type="text" name="log" id="user_login" class="input" value="" size="20"></label>
        </p>
        <p>
            <label for="user_pass">Password<br>
                <input type="password" name="pwd" id="user_pass" class="input" value="" size="20"></label>
        </p>
        <div class="login__rememberme">
            <label for="rememberme">
                <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                Remember Me
            </label>
        </div>
        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Log In">
            <input type="hidden" name="redirect_to" value="">
            <input type="hidden" name="testcookie" value="1">
        </p>
    </form>
    <p id="nav">
        <a href="#">Lost your password?</a>
    </p>
    <p id="backtoblog">
        <a href="#">&larr; Back to Site</a>
    </p>
</div>
</body>
</html>
