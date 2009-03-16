<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:feedburner="http://rssnamespace.org/feedburner/ext/1.0">
  <xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"
    doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"/>

  <xsl:template match="/">
    <xsl:element name="html">
      <xsl:attribute name="id">sitemap</xsl:attribute>
      <head>
        <title>Molinos CMS Sitemap</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
      </head>
      <body>
        <h1>Карта сайта</h1>
        <p>Этот файл не предназначен для чтения, он предназначен для поисковых роботов.</p>
        <p>Подробности можно узнать на сайте <a href="http://www.sitemap.org/">sitemap.org</a>.</p>
        <p><small>PS: то, что вы видите этот текст, означает, что модуль <a href="http://code.google.com/p/molinos-cms/wiki/mod_sitemap">sitemap</a> для Molinos CMS работает нормально.</small></p>
        <hr />
        <p><em>Truly yours, <a href="http://cms.molinos.ru/">Molinos CMS</a>.</em></p>
      </body>
    </xsl:element>
   </xsl:template>

   <xsl:template match="urlset">
     <body>
       <h1>Sitemap</h1>
       <p>Hello.</p>
     </body>
   </xsl:template>
</xsl:stylesheet>
