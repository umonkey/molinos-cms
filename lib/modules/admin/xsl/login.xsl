<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="../../base/forms.xsl" />
  <xsl:import href="../../base/redirect.xsl" />

  <xsl:variable name="api" select="/page/@api" />
  <xsl:variable name="base" select="/page/@base" />
  <xsl:variable name="next" select="/page/@next" />

  <xsl:output
    omit-xml-declaration="yes"
    method="xml"
    version="1.0"
    encoding="UTF-8"
    indent="yes"
    doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
    doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
    />

  <xsl:template match="page">
    <xsl:apply-templates select="document(concat($api,'auth/info.xml'))/node" />
  </xsl:template>

  <xsl:template match="node[@id]">
    <xsl:call-template name="redirect">
      <xsl:with-param name="href">
        <xsl:choose>
          <xsl:when test="$next">
            <xsl:value-of select="$next" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$base" />
            <xsl:text>admin</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="node[not(@id)]">
    <html>
      <head>
        <base href="{$base}" />
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
          <xsl:apply-templates select="document(concat($api,'auth/form.xml'))/form" />
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
