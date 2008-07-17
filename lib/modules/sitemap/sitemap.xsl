<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:feedburner="http://rssnamespace.org/feedburner/ext/1.0">
  <xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"
    doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"/>

  <xsl:template match="/">
    <xsl:element name="html">
      <xsl:attribute name="id">sitemap</xsl:attribute>
      <head>
        <title>Molinos CMS Sitemap</title>
      </head>
      <body>
        <h1>Oops.</h1>
        <p>This file is not for your reading pleasure; it's for web crawlers.</p>
        <p>You may refer to <a href="http://www.sitemap.org/">sitemap.org</a> to learn about site maps.</p>
        <p><small>PS: the fact that you can see this confirms that the <a href="http://code.google.com/p/molinos-cms/wiki/mod_sitemap">sitemap module</a> is working.</small></p>
        <hr />
        <p><em><a href="http://cms.molinos.ru/">Molinos CMS v8.05</a> for your domains</em></p>
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
