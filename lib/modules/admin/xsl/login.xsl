<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="../../base/forms2.xsl" />

  <xsl:output omit-xml-declaration="yes" method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

  <xsl:template match="page[@status='401']">
    <html>
      <head>
        <base href="{@base}" />
        <link rel="stylesheet" href="lib/modules/admin/styles/admin/00reset.css" type="text/css" />
        <link rel="stylesheet" href="lib/modules/auth/styles/admin/10login.css" type="text/css" />
        <script type="text/javascript" src="lib/modules/admin/scripts/admin/00jquery.js" />
        <script type="text/javascript" src="lib/modules/auth/scripts/admin/10login.js" />

        <!--
        <script type="text/javascript" src="{@prefix}/.admin.js" />
        -->
      </head>
      <body>
        <div id="login-form">
          <xsl:apply-templates select="form" />
        </div>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="form">
    <form method="post" action="{@action}" id="login">
      <h1>Вход в Molinos CMS</h1>
      <xsl:for-each select="input[@name='auth_type']/option">
        <label class="switch">
          <input type="radio" name="{../@name}" value="{@value}">
            <xsl:if test="@selected">
              <xsl:attribute name="checked">checked</xsl:attribute>
            </xsl:if>
          </input>
          <span>
            <xsl:value-of select="text()" />
          </span>
        </label>

        <xsl:variable name="mode" select="@value" />

        <div id="{$mode}" class="tab">
          <xsl:apply-templates select="../../input[@type='group' and @mode=$mode]/input" />
        </div>
      </xsl:for-each>

      <xsl:apply-templates select="input[@type='submit']" />
    </form>
  </xsl:template>
</xsl:stylesheet>
