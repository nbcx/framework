<!DOCTYPE HTML>
<html>
	<head>
		<title>Data&Newbie</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<style type="text/css">
		*{padding: 0px;margin: 0px;}
		body{ background: #fff; font-family: '微软雅黑'; color: #333; font-size: 16px; }
		h2{margin-bottom: 5px;margin-top: 10px;}
		table{border-collapse:collapse;}
		th,td{border-right: 1px solid #CCC;border-bottom: 1px solid #CCC;min-width: 50px;background: #EEEFFF;padding: 5px;}
		.breakall{word-break:break-all;}
		.nav{width:auto;height: auto;position: fixed!important;position: absolute;top:20px!important;top:20px;right:0px;padding-right:0px;top: expression(eval(document.compatMode && document.compatMode=='CSS1Compat') ? documentElement.scrollTop+(documentElement.clientHeight - this.clientHeight):document.body.scrollTop+(document.body.clientHeight - this.clientHeight));}
		.nav td{padding:5px;padding-right:15px;border-right: none;background: #ccc;border-bottom: 1px solid #AAA;}
		.nav td:hover{background: #BBB;}
		.nav a{text-decoration: none;}
		.copyright{ padding: 12px 0px; color: #999; }
		.copyright a{ color: #000; text-decoration: none; }
		</style>
	</head>
	<body>
		<h2>表名:<?=$tableName ?></h2>
		<div>总条数 <?=$num ?></div>
		
		<?php if(!empty($result)) {?>
		<div style="padding: 12px 0px;">
		<table>
			<?php $th=true; foreach ($result as $v ){ ?>
				<?php if($th){ $th = false;?>
				<tr>
				<?php foreach ($v as $key=>$val){?>
		           <th><?=$key?></th>
		          <?php }?>
				</tr>
				<?php }?>
				<tr>
		          <?php foreach ($v as $key=>$val){?>
		           <td><?=$val?></td>
		          <?php }?>
				</tr>
			<?php }?>
		</table>
		</div>
		<?php }?>
		
		<div style="height: 30px;"></div>
		
		<div class="nav">
		<?php if(!empty($tables)){?>
		      <table>
		      <?php foreach ($tables as $v){?>
		          <tr>
		          <td><a href="<?=URL?>debug/data-<?=$v?>"><?=$v?></a></td>
		          </tr>
		      <?php }?>
		      </table>
		<?php }?>
		</div>
		<div class="copyright">
		<p><a title="官方网站" href="http://nb.cx" target="_blank">Newbie</a><sup>3.0</sup> { Fast & Simple OOP PHP Framework } -- [ We Can Do It Just Newbie ]</p>
		</div>
	</body>
</html>












