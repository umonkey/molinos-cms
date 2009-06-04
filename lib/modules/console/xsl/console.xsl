<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	
	<xsl:output omit-xml-declaration="yes" method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>
	
	<xsl:template match="/page">
		
		<html lang="ru">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>Console</title>
				
				<xsl:comment><![CDATA[[if IE]><![if !IE]><![endif]]]></xsl:comment><base href="{/page/@base}" /><xsl:comment><![CDATA[[if IE]><![endif]><![endif]]]></xsl:comment>
				<xsl:comment><![CDATA[[if IE]>]]>&lt;base href="<xsl:value-of select="/page/@base"/>"> &lt;/base><![CDATA[<![endif]]]></xsl:comment>
				
				<meta name="description" content="" />
				<meta name="keywords" content="" />
				<meta name="robots" content="index, follow" />
				
				<link rel="stylesheet" href="./lib/modules/console/styles/basis.reset.css" type="text/css" />
				<link rel="stylesheet" href="./lib/modules/console/styles/basis.typography.css" type="text/css" />
				<link rel="stylesheet" href="./lib/modules/console/styles/console.css" type="text/css" />
				
				<script src="./lib/modules/console/scripts/jquery.js" type="text/javascript"></script>
				<script src="./lib/modules/console/scripts/jqXMLUtils_beta3.js" type="text/javascript"></script>
				<script src="./lib/modules/console/scripts/console-core.js" type="text/javascript"></script>
				<script src="./lib/modules/console/scripts/console-commands.js" type="text/javascript"></script>
			</head>
			<body>
				
				<div id="console">
					<div class="container">
						<h1>Вспомогательные инструменты</h1>
						
						<ul class="panel-selector">
							<li class="active">Консоль</li>
							<li>XML API - конструктор</li>
							<li>XML API - инспектор</li>
						</ul>
						
						<ul class="panels">
							<li class="panel console">
								<div class="display">
									<ul />
								</div>
								<div class="input">
									<form action="">
										<input type="text" class="console-command" />
										<input type="submit" class="submit" value="»" />
										<span class="clear">очистить</span>
									</form>
								</div>
								<div class="note">Введите "help" для отображения списка доступных команд</div>
							</li>
							<li class="panel xmlapi-constructor">
								<div class="display">
									<ul />
									Здесь пока ничего нет
								</div>
							</li>
							<li class="panel xmlapi-inspector">
								<div class="display">
									<ul />
									Здесь пока тоже пусто
								</div>
							</li>
						</ul>
						
					</div>
				</div>
				
			</body>
		</html>
		
	</xsl:template>
	
</xsl:stylesheet>