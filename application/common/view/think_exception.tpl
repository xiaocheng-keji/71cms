<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>该页面忙不过来了，请刷新重试～</title>
	</head>
	<style>
		body {
			background-color: #FAFAFA;
		}

		.wrap {
			width: 1200px;
			margin: 0 auto;
		}

		.box-top {
			width: 1200px;
			height: 20px;
			background: #364476;
			border-radius: 19px;
		}

		.box {
			width: 1160px;
			min-height: 568px;
			margin: 0 auto;
			background: #FFFFFF;
			box-shadow: 0px 4px 16px 0px rgba(0, 0, 0, 0.1);
			padding-top: 1px;
			text-align: center;
		}

		.box .img {
			width: 835px;
			height: 447px;
			margin: 0px auto 0;
			padding-left: 42px;
		}

		.box .btn {
			display: inline-block;
			background-color: #F53535;
			color: #FFFFFF;
			width: 120px;
			height: 30px;
			line-height: 30px;
			border-radius: 30px;
			text-decoration: none;
		}

		.box-bottom-wrap {
			text-align: center;
			height: 33px;
			overflow: hidden;
		}

		.box-bottom {
			display: inline-block;
			width: 66px;
			height: 66px;
			background: #364476;
			border-radius: 50%;
			position: relative;
			margin-top: -33px;
		}

		.box-bottom .round {
			display: inline-block;
			width: 33px;
			height: 33px;
			background: #FAFAFA;
			border-radius: 50%;
			position: absolute;
			left: 50%;
			top: 50%;
			margin: -16.5px 0 0 -16.5px;
		}
	</style>
	<body>
		<div class="wrap">
			<div class="box-top"></div>
			<div class="box">
				<img src="/image/404.png" class="img">
				<p>该页面忙不过来了，请刷新重试～</p>
				<a class="btn" href="javascript:void(0)" onclick="location.reload()">立即刷新</a>
			</div>
			<div class="box-bottom-wrap">
				<div class="box-bottom">
					<div class="round"></div>
				</div>
			</div>
		</div>
	</body>
</html>
