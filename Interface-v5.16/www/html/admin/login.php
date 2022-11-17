<?php
/*
*  Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license.
*/

require 'scripts/pi-hole/php/password.php';

// Go directly to index, if authenticated.
if ($_SESSION['auth']) {
    header('Location: index.php');
    exit;
}

require 'scripts/pi-hole/php/theme.php';
require 'scripts/pi-hole/php/header.php';
?>
<body class="hold-transition layout-boxed login-page">
<div class="box login-box">
    <section style="padding: 15px;">
        <div class="login-logo">
            <div class="text-center">
                <img src="img/logo.svg" alt="Pi-hole logo" class="loginpage-logo">
            </div>
            <div class="panel-title text-center"><span class="logo-lg" style="font-size: 25px;">Pi-<b>hole</b></span></div>
        </div>
        <!-- /.login-logo -->

        <div class="card">
            <div class="card-body login-card-body">
                <div id="cookieInfo" class="panel-title text-center text-red" style="font-size: 150%" hidden>请检查浏览器是否允许使用Cookie<code><?php echo $_SERVER['HTTP_HOST']; ?></code></div>
                <?php if ($wrongpassword) { ?>
                <div class="form-group has-error login-box-msg">
                    <label class="control-label"><i class="fa fa-times-circle"></i>密码错误！</label>
                </div>
                <?php } ?>

                <form action="" id="loginform" method="post">
                    <div class="form-group login-options has-feedback<?php if ($wrongpassword) { ?> has-error<?php } ?>">
                        <div class="pwd-field">
                            <input type="password" id="loginpw" name="pw" class="form-control" placeholder="密码" autocomplete="current-password" autofocus>
                            <span class="fa fa-key form-control-feedback"></span>
                        </div>
                        <div>
                            <input type="checkbox" id="logincookie" name="persistentlogin">
                            <label for="logincookie">自动登录（七天内）</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary form-control"><i class="fas fa-sign-in-alt"></i>&nbsp;&nbsp;&nbsp;登录</button>
                    </div>
                </form>
                <br>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="box box-<?php if (!$wrongpassword) { ?>info collapsed-box<?php } else { ?>danger<?php }?>">
                            <div class="box-header with-border pointer no-user-select" data-widget="collapse">
                                <h3 class="box-title">忘记密码？</h3>
                                <div class="box-tools pull-right">
                                    <button type="button" class="btn btn-box-tool">
                                        <i class="fa <?php if ($wrongpassword) { ?>fa-minus<?php } else { ?>fa-plus<?php } ?>"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="box-body">
                                <p>首次安装 Pi-hole 后，会生成密码并显示给用户。以后无法检索密码，但可以在终端输入以下命令设置新密码（或通过设置空密码禁用密码）
                                </p>
                                <pre>sudo pihole -a -p</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.login-card-body -->
            <div class="login-footer" style="margin-top: 15px; display: flex; justify-content: space-between;">
                <a class="btn btn-default btn-sm" role="button" href="https://docs.pi-hole.net/" target="_blank"><i class="fas fa-question-circle"></i> 资料</a>
                <a class="btn btn-default btn-sm" role="button" href="https://github.com/pi-hole/" target="_blank"><i class="fab fa-github"></i> Github</a>
                <a class="btn btn-default btn-sm" role="button" href="https://discourse.pi-hole.net/" target="_blank"><i class="fab fa-discourse"></i> Pi-hole 论坛</a>
            </div>
        </div>
    </section>
</div>

<div class="login-donate">
    <div class="text-center" style="font-size:125%">
        <strong>如果您觉得Pi-hole有用，请<a href="https://pi-hole.net/donate/" rel="noopener" target="_blank"><i class="fa fa-heart text-red"></i> 赞助</a></strong> Pi-hole。
    </div>
</div>
<script src="scripts/pi-hole/js/footer.js?v=<?php echo $cacheVer; ?>"></script>
</body>
</html>