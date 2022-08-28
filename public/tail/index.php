<?php
// if (!in_array($_SERVER['REMOTE_ADDR'], ['172.19.0.1'])) {
//     header("HTTP/1.0 404 Not Found");
//     die();
// }

?><div class="content">
    <label class="filter_label" id="userId_filters_label">User filter: <div id="userId_filters"></div></label>
    <div class="r_buttons"><button class="clear_table">Clear</button></div>
    <table class="table table-list table_tail" style="width:100%;">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Method</th>
                <th>URI</th>
                <th>Request</th>
                <th>Response</th>
                <th>Headers</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
</div>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<style type="text/css">
    .table th {
        padding: 3px 5px;
        color: #999;
        font-size: 15px;
        white-space: nowrap;
    }

    .r_buttons {
        float: right;
        margin: 6px 0 0 0;
    }

    .clear_table {
        border: 1px solid #ccc;
        border-radius: 5px;
        color: #999;
        padding: 0 10px;
        cursor: pointer;
    }

    .clear_table:hover {
        color: #666;
        border-color: #999;
    }

    .prettyJson li {
        list-style: none;
    }

    .prettyJson .parent>ul {
        display: none;
    }

    .show_hide_b {
        left: -20px;
        position: absolute;
        top: 0;
        cursor: pointer;
        color: #c0c0c0;
        font-family: arial;
    }

    li.parent {
        position: relative;
    }

    .prettyJson {
        padding-left: 20px;
    }

    .k {
        color: #666;
        margin-right: 5px;
    }

    #userId_filters input {
        margin-right: 5px;
    }

    #userId_filters span {
        margin-right: 20px;
    }

    #userId_filters {
        display: inline-block;
        padding-left: 20px;
    }

    body {
        font-size: 15px;
    }

    .phpSpeed {
        color: #999;
        font-size: 12px;
        display: block;
    }

    .content {
        margin: 15px;
        font-size: 16px;
    }

    body {
        color: #555;
    }

    label {
        font-weight: normal;
    }
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script type="text/javascript">
    function prettyJson(json) {
        var res = "<ul>";
        $.each(json, recurse);

        function recurse(key, val) {
            res += "<li" + (val instanceof Object ? ' class="parent"' : '') + ">";
            if (val instanceof Object) {
                if (jQuery.isArray(val)) {
                    res += key + ":[ <ul>";
                    $.each(val, recurse);
                    res += "</ul>],";
                } else {
                    res += key + ":{ <ul>";
                    $.each(val, recurse);
                    res += "</ul>},";
                }
            } else {
                res += '<span class="k">' + key + ':</span> ' + val + ',';
            }
            res += "</li>";
        }
        res += "</ul>";

        return prettyJsonProcessLines($(res));
    }

    function prettyJsonProcessLines(this1) {
        this1.find("li.parent").each(function() {
            ul = $(this).find('ul:first');
            if (ul.is(":visible")) $(this).prepend('<div class="show_hide_b">&#9660;</div>');
            else $(this).prepend('<div class="show_hide_b">&#9658;</div>');
        });
        return this1.html();
    }
    $('.clear_table').on('click', function() {
        $('.table_tail tbody tr').remove();
    });
    $(document).on('click', ".show_hide_b", function() {
        ul = $(this).closest('li').find('ul:first');

        if (ul.is(":visible")) $(this).html('&#9658;');
        else $(this).html('&#9660;');

        ul.slideToggle('fast');

        return false;
    });

    function twoDigits(d) {
        if (0 <= d && d < 10) return "0" + d.toString();
        if (-10 < d && d < 0) return "-0" + (-1 * d).toString();
        return d.toString();
    }
    Date.prototype.toMysqlFormat = function() {
        return this.getFullYear() + "-" + twoDigits(1 + this.getMonth()) + "-" + twoDigits(this.getDate()) + " " + twoDigits(this.getHours()) + ":" + twoDigits(this.getMinutes()) + ":" + twoDigits(this.getSeconds());
    };
    Date.prototype.toMysqlFormatTime = function() {
        return twoDigits(this.getHours()) + ":" + twoDigits(this.getMinutes()) + ":" + twoDigits(this.getSeconds());
    };


    function c(str) {
        console.log(str);
    }

    function uuidToShort(uuid) {
        return uuid.replace(/-.+$/i, '');
    }
    var userIdNames = {};

    function userNameById(id) {
        return typeof userIdNames[id] != 'undefined' ? userIdNames[id] : id;
    }
    var userIdColor = {
        'null': '#333'
    };

    function userIdColorNew() {
        var colors = ['#459803', '#034e98', '#360398', '#550398', '#987103']
        return colors[$('#userId_filters label').length - 1];
    }

    // PushStream
    const pusher = new Pusher('scheduler_app123', {
        cluster: 'mt1',
        enabledTransports: ['ws', 'wss'],
        forceTLS: false,
        wsHost: '192.168.1.3',
        wsPort: 6001,
    });
    const channel = pusher.subscribe('http_logs');
    channel.bind('App\\Events\\HttpLogEvent', function(data) {
        // console.log(data.message);
        data = data.payload;

        // c(data.type + ' = function');
        // c(data);

        if (typeof data.request == 'undefined') {
            console.log('Bad "data" recieved');
            console.log(data);
            return;
        }

        var userId;
        if (data.userId) userId = data.userId;
        else if (data.data?.payload?.userId && data.data.payload.userId != null) userId = data.data.payload.userId;
        else if (data.response?.data?.currentUser?.id) userId = data.response.data.currentUser.id;
        else if (typeof data.response != 'undefined' && typeof data.response.data != 'undefined' && typeof data.response.data.channelId != 'undefined' && typeof data.response.data.id != 'undefined') userId = data.response.data.id;
        else userId = null;

        if (userId != null) {
            var isset = false;
            $('#userId_filters input').each(function() {
                if ($(this).attr('value') == userId) {
                    isset = true;
                    return false;
                }
            });
            if (!isset) $('#userId_filters').append('<label class="in_' + userId + '"><input type="checkbox" value="' + userId + '"><span>' + userId + '</span></label>');
        }

        // get user name
        if ((
                (data.uri == '/auth-user/user' || data.uri == '/auth-admin/user' || data.uri == '/auth/login') ||
                (data.uri == '/graphql' && data.request?.data?.operationName && data.request.data.operationName == 'CurrentUser')
            ) &&
            userNameById(userId) == userId
        ) {
            if (data.uri == '/graphql') userIdNames[userId] = data.response.data.currentUser.name;
            else userIdNames[userId] = data.response.data.firstName + ' ' + data.response.data.lastName;

            $('#userId_filters .in_' + userId + ' span').attr('title', userId).text(userIdNames[userId]);
            $('.table_tail tr').each(function() {
                $(this).find('td:eq(1)').text($(this).find('td:eq(1)').text().replace(userId, userNameById(userId)));
            });
        }
        if (typeof userIdColor[userId] == 'undefined') {
            userIdColor[userId] = userIdColorNew();
        }

        // userId filter
        if ($('#userId_filters input:checked').length) {
            var ret = true;
            $('#userId_filters input:checked').each(function() {
                if ($(this).attr('value') == userId) {
                    ret = false;
                    return false;
                }
            });
            if (ret) return;
        }

        $('.table_tail tbody').prepend(`
                 <tr ` + (data.code != 200 && data.code != 201 ? 'style="background-color: #FED3D3;"' : (data.uri.indexOf('http') == 0 ? 'style="background-color: #fffc001a;"' : '')) + `>
                    <td style="color:#999;">` + (new Date()).toMysqlFormatTime() + `</td>
                    <td style="color: ` + userIdColor[userId] + `">` + userNameById(userId) + `</td>
                    <td title="Response code: ` + data.code + `">` + data.method + ` <span class="phpSpeed">` + data.phpProcessTime + `ms</span></td>
                    <td>` + data.uri + (data.uri == '/graphql' ? ' <span class="phpSpeed">' + data.request?.data?.operationName + '</span>' : '') + `</td>
                    <td>` + (!$.isEmptyObject(data.request.data) ? `<div class="prettyJson">` + prettyJson(data.request) + `</div>` : '') + `</td>
                    <td>` + (!$.isEmptyObject(data.response) ? `<div class="prettyJson">` + prettyJson(data.response) + `</div>` : '') + `</td>
                    <td>` + (!$.isEmptyObject(data.headers) ? `<div class="prettyJson">` + prettyJson(data.headers) + `</div>` : '') + `</td>
                </tr>
            `);
    });
    channel.bind('my-event', function() {
        console.log(`hi ${this.name}`);
    }, {
        name: 'Pusher'
    });
    pusher.connection.bind('state_change', function(states) {
        // states = {previous: 'oldState', current: 'newState'}
        $('div#status').text("Channels current state is " + states.current);
    });
    // end PushStream
</script>
