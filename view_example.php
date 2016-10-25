<html>
<head>
    <title>ptimer管理中心</title>
    <link href="resource/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="resource/dist/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <style type="text/css">
        body {
            padding-top: 38px;
            font-size: 14px;
            font-family: Microsoft YaHei, '宋体', Tahoma, Helvetica, Arial;
        }

        h1, h2, h3 {
            margin: 0px;
        }

        .mynav {
            padding-bottom: 8px;
            border-bottom: solid 1px #ccc;
        }

        th, td {
            text-align: center;
            font-size: 14px;
        }

        .center {
            margin: 0 auto;
        }

        .p10 {
            padding: 10px;
        }

        .mt50 {
            margin-top: 50px;
        }

        .mt110 {
            margin-top: 110px;
        }

        .mt10 {
            margin-top: 10px;
        }

        .mt20 {
            margin-top: 20px;
        }

        .mt30 {
            margin-top: 30px;
        }

        .h34 {
            height: 34px;
        }
    </style>
    <script type="text/javascript" src="resource/dist/js/jquery.min.js"></script>
    <script type="text/javascript" src="resource/dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="resource/dist/js/bootstrap-button.js"></script>
    <script type="text/javascript" src="resource/dist/js/bootstrap-modal.js"></script>
    <script type="text/javascript" src="resource/dist/js/vue.min.js"></script>
    <script type="text/javascript" src="resource/dist/js/bootstrap-datetimepicker.min.js"></script>
</head>
<body>
<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                        class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">新增任务</h4>
            </div>
            <div class="modal-body">
                <form action="">
                    <div class="input-group">
                        <span class="input-group-addon">执行命令</span>
                        <input type="text" class="form-control" name="command"
                               placeholder="for example: /usr/local/php/bin/php boot.php">
                    </div>
                    <div class="input-group mt10">
                        <span class="input-group-addon">执行频率</span>
                        <input type="text" name="interval" class="form-control"
                               placeholder="单位秒, 输入值>=1，为周期执行。不填为触发执行一次">
                    </div>
                    <div class="input-group mt10">
                        <span class="input-group-addon">触发时间</span>
                        <input size="19" type="text" name="trigger_time" class="form-control form_datetime"
                               placeholder="请选择执行一次的触发时间">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="save" type="button" class="btn btn-primary" data-loading-text="处理中...">保存</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->

<div class="navbar-default navbar-fixed-top mynav">
    <div style="background: #000;">
        <div class="container center">
            <h5 style="color: white;">欢迎使用ptimer, <span
                    style="color: lightgoldenrodyellow;font-weight: bold;padding-left: 5px;">使用该页面前, 保证ptimer服务已经启动!</span>
            </h5>
        </div>
    </div>
    <div class="container row center">
        <div class="logo navbar-brand col-md-2">
            <h1>Ptimer</h1>
            <h6>
                <small class="p10">&copy;江济平</small>
            </h6>
        </div>
        <div class="col-md-4">
            <button id="add" class="btn btn-success mt20 p10" data-toggle="modal" type="button">
                <span class="glyphicon glyphicon-plus-sign"> 新增任务</span>
            </button>
            <button id="flush" data-loading-text="加载中,请耐心等待..." class="btn btn-primary mt20 p10" type="button">
                <span class="glyphicon glyphicon-refresh"> 刷新任务列表</span>
            </button>
        </div>
        <div class="col-md-4 col-md-offset-2 mt30">
            <form method="post" action="">
                <div class="input-group ssearch">
                    <input type="text" name="keywords" class="form-control" placeholder="请输入关键词"
                           style="font-size: 13px;">
                                <span class="input-group-btn">
                                    <button class="btn btn-default h34" type="submit">
                                        <span class="glyphicon glyphicon-search"></span></button>
                                </span>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="container mt110 center">
    <div id="crontab-list">
        <h2 class="lead"><span class="glyphicon glyphicon-list">计划任务列表</span></h2>
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th>任务编号</th>
                <th>命令</th>
                <th>执行类型</th>
                <th>时间间隔(s)</th>
                <th>触发时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody id="clist">
            <template v-for="item in items">
                <tr>
                    <td>{{ item.id }}</td>
                    <td>
                        <div style="word-break: break-all;word-wrap: break-word; width: 450px;">
                            {{item.command}}
                        </div>
                    </td>
                    <td>
                        <div v-if="item.is_persistent">周期执行</div>
                        <div v-else>一次</div>
                    </td>
                    <td>
                        <div v-if="item.interval">{{ item.interval}}</div>
                        <div v-else class="text-muted">——</div>
                    <td>
                        <div v-if="item.triggerTime">{{item.triggerTime}}</div>
                        <div v-else class="text-muted">——</div>
                    </td>
                    <td>
                        <a href="javascript:;" onclick="return customerConfirm($(this))" class="del" rel="{{item.id}}">
                            <span class="glyphicon glyphicon-minus-sign text-danger"></span>
                        </a>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</div>
</body>
<script type="text/javascript">
    var bind;
    $(".form_datetime").datetimepicker({format: 'yyyy-mm-dd hh:ii:ss'});
    function customerConfirm(a) {
        var result = confirm('确定删除该项吗?');
        var lock = false;
        if (result) {
            a.parents('tr').css("opacity", 0.5);
            //删除计划任务
            if (lock) {
                return;
            }
            lock = true;
            $.ajax({
                type: 'POST',
                url: '/index.php',
                data: {
                    key: 'remove_get',
                    timer_id: a.attr("rel")
                },
                dataType: 'json',
                success: function (data) {
                    if (bind) {
                        bind.items = data.items;
                    } else {
                        bind = new Vue({
                            el: '#clist',
                            data: {
                                items: data.items
                            }
                        });
                    }
                    lock = false;
                }
            });
        }
    }
    function form_reset() {
        $("input[type=text][name=command]").val('');
        $("input[type=text][name=interval]").val('');
        $("input[type=text][name=trigger_time]").val('');
        $('#myModal').modal('hide');
    }
    function flush() {
        var $btn = $("#flush").button('loading');
        $.ajax({
            type: 'POST',
            url: '/index.php',
            data: {
                key: 'save_get'
            },
            dataType: 'json',
            success: function (data) {
                if (bind) {
                    bind.items = data.items;
                } else {
                    bind = new Vue({
                        el: '#clist',
                        data: {
                            items: data.items
                        }
                    });
                }
                $btn.button('reset');

            }
        });
    }
    $(document).ready(function () {
        $("#flush").on('click', function () {
            flush();
        });
        $("#add").click(function () {
            $('#myModal').modal();
        });
        $("button#save").on('click', function () {
            var btn = $("button#save").button('loading');
            $.ajax({
                type: 'POST',
                url: '/index.php',
                data: {
                    key: 'add_get',
                    command: $("input[type=text][name=command]").val(),
                    interval: $("input[type=text][name=interval]").val(),
                    trigger_time: $("input[type=text][name=trigger_time]").val()
                },
                dataType: 'json',
                success: function (data) {
                    if (data.code == 0) {
                        bind.items = data.items;
                        btn.button('reset');
                        form_reset();
                    } else if (data.code == 1) {
                        alert(data.msg);
                        btn.button('reset');
                    }

                },
                error: function (e) {
                    console.log(e);
                    form_reset();
                }
            });
            flush();
        });
        flush();
    });
</script>
</html>