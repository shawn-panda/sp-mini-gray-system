<?php
$is_submit   = ! empty($_POST);
$redis_ok    = FALSE;
$prefix      = 'AB_TESTING_RULE';
$field_table = array('source', 'type', 'key', 'value', 'start_timestamp', 'end_timestamp');
$title       = array('一','二','三','四','五','六','七','八');

if( class_exists('Redis') )
{
    $redis = new Redis();
    $redis_ok = $redis->connect('127.0.0.1', 6379);
}

if( $redis_ok && $is_submit )
{
    for($i=0; $i<8; $i++)
    {
        $key = $prefix . $i;

        if( empty($_POST[$key . '_enabled']) )
        {
            $redis->delete($key);
            continue;
        }

        $rule = array(
            'source'          => trim($_POST[$key . '_source']),
            'type'            => trim($_POST[$key . '_type']),
            'key'             => trim($_POST[$key . '_key']),
            'value'           => trim($_POST[$key . '_value']),
            'start_timestamp' => strtotime($_POST[$key . '_start_timestamp']),
            'end_timestamp'   => strtotime($_POST[$key . '_end_timestamp']),
        );

        $redis->hMset($key, $rule);
    }
    
    header('Location:/');
}

$map = array();

if( $redis_ok && ! $is_submit )
{
    for($i=0; $i<8; $i++)
    {
        $key  = $prefix . $i;
        $rule = $redis->hMget($key, $field_table );
        if( empty($rule['source'])  )
        {
            break;
        }
        $rule['enabled'] = TRUE;
        $map[$key] = $rule;
    }
}

if( empty($map) && file_exists('demo-public.php') )
{
    $demo = require('demo-public.php');
    foreach($demo as $i => $rule)
    {
        $key  = $prefix . $i;
        $map[$key] = $rule;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AB_TESTING_RULE-TABLE</title>
    <link rel="stylesheet" href="assets/css/default.css">
    <script src="assets/js/jquery-3.1.0.min.js"></script>
    <script src="assets/js/laydate.js"></script>
</head>
<body>

<div class="content">
    <h2>灰度规则配置</h2>
    <form method="post" action="index.php"><?php
for($i=0; $i<8; $i++)
{
    $key  = $prefix . $i;

?>
        <div class="rule_item" <?php if( empty($map[$key]) ) { echo ' style="display:none;"'; } ?>>
            <table>
                <tr>
                    <td>
                        <strong>规则<?php echo $title[$i];?></strong>
                    </td>
                </tr>
            </table>
            <table>
                <tr>
                    <td width="28%">Enabled</td>
                    <td>
                        <section><div class="checkbox">
                            <input id="<?php echo $key;?>_enabled" name="<?php echo $key;?>_enabled" type="checkbox"<?php if( isset($map[$key]['enabled']) && $map[$key]['enabled'] ) { echo ' checked="checked"'; } ?> />
                            <label for="<?php echo $key;?>_enabled"></label>
                        </section></div>
                    </td>
                </tr>
                <tr>
                    <td>Souce</td>
                    <td>
                        <select name="<?php echo $key;?>_source">
                            <option value="uri"<?php if( isset($map[$key]['source']) && 'uri' == $map[$key]['source'] ) { echo ' selected="selected"'; } ?>>uri</option>
                            <option value="host"<?php if( isset($map[$key]['source']) && 'host' == $map[$key]['source'] ) { echo ' selected="selected"'; } ?>>host</option>
                            <option value="cookie"<?php if( isset($map[$key]['source']) && 'cookie' == $map[$key]['source'] ) { echo ' selected="selected"'; } ?>>cookie</option>
                            <option value="ip"<?php if( isset($map[$key]['source']) && 'ip' == $map[$key]['source'] ) { echo ' selected="selected"'; } ?>>ip</option>
                            <option value="random"<?php if( isset($map[$key]['source']) && 'random' == $map[$key]['source'] ) { echo ' selected="selected"'; } ?>>random</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Type</td>
                    <td>
                        <select name="<?php echo $key;?>_type">
                            <option value="int"<?php if( isset($map[$key]['type']) && 'int' == $map[$key]['type'] ) { echo ' selected="selected"'; } ?>>int</option>
                            <option value="set"<?php if( isset($map[$key]['type']) && 'set' == $map[$key]['type'] ) { echo ' selected="selected"'; } ?>>set</option>
                            <option value="range"<?php if( isset($map[$key]['type']) && 'range' == $map[$key]['type'] ) { echo ' selected="selected"'; } ?>>range</option>
                            <option value="threshold"<?php if( isset($map[$key]['type']) && 'threshold' == $map[$key]['type'] ) { echo ' selected="selected"'; } ?>>threshold</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Key</td>
                    <td>
                        <input type="text" name="<?php echo $key;?>_key" value="<?php if( ! empty($map[$key]['key']) ) { echo $map[$key]['key']; } ?>" />
                    </td>
                </tr>
                <tr>
                    <td>Value</td>
                    <td>
                        <input type="text" name="<?php echo $key;?>_value" value="<?php if( ! empty($map[$key]['value']) ) { echo $map[$key]['value']; }  ?>" />
                    </td>
                </tr>
                <tr>
                    <td>StartDateTime</td>
                    <td><input type="text" name="<?php echo $key;?>_start_timestamp" value="<?php if( isset($map[$key]['start_timestamp']) && 0 < $map[$key]['start_timestamp'] ){ echo date('Y-m-d H:i:s', $map[$key]['start_timestamp']);}?>" onclick="laydate({istime: true, format: 'YYYY-MM-DD hh:mm:ss'})"></td>
                </tr>
                <tr>
                    <td>EndDateTime</td>
                    <td><input type="text" name="<?php echo $key;?>_end_timestamp" value="<?php if( isset($map[$key]['end_timestamp']) && 0 < $map[$key]['end_timestamp'] ){ echo date('Y-m-d H:i:s', $map[$key]['end_timestamp']);}?>" onclick="laydate({istime: true, format: 'YYYY-MM-DD hh:mm:ss'})"></td>
                </tr>
            </table>
            <br />
        </div>
<?php
}
?>
        <div>
            <input type="submit" class="button" value="SAVE" />
            <input type="button" class="button btn-add" id="add_new_rule" value="ADD" />
        </div>
        <br />
        <br />
        <br />
        <br />
        <br />
        <br />
    </form>
</div>
<script type="text/javascript">
    $("#add_new_rule").click(function(){
        var hidden_list = $("div.rule_item:hidden");
        if( 'undefined' != typeof(hidden_list[0]) ) {
            hidden_list[0].style.display = 'block';
        }
        if( 'undefined' == typeof(hidden_list[1]) ) {
            $("#add_new_rule").hide();
        }
    });
</script>
</body>
</html>
