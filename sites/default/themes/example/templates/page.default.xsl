<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:variable name="api" select="/page/@api" />

  <xsl:template match="/page">
    <html>
      <head>
        <base href="{@base}"></base>
        <title>
          <xsl:value-of select="@title" />
        </title>
        <link rel="stylesheet" type="text/css" href="{@prefix}/styles/lib/refpoint.reset.css" />
        <link rel="stylesheet" type="text/css" href="{@prefix}/styles/lib/refpoint.typography-16.css" />
        <link rel="stylesheet" type="text/css" href="{@prefix}/styles/lib/refpoint.logo.css" />
        <link rel="stylesheet" type="text/css" href="{@prefix}/styles/page.index.css" />
        <meta name="generator" content="Molinos CMS v{@version}" />
      </head>
      <body>
        <div id="navigation">
          <xsl:apply-templates select="widgets/widget[@name='sections']" />
        </div>
        <div id="content">
          <h1 id="logo">
            <a href=".">
              <span>Molinos CMS</span>
            </a>
          </h1>

          <xsl:apply-templates select="document(concat($api,'node/list.xml?class=article'))/nodes/node" mode="list" />
        </div>
      </body>
    </html>
  </xsl:template>

  <!-- Меню -->
  <xsl:template match="widget[@name='sections']">
    <ul>
      <xsl:apply-templates select="section/children/section" mode="menu" />
    </ul>
  </xsl:template>

  <xsl:template match="section" mode="menu">
    <li>
      <xsl:attribute name="class">
        <xsl:if test="position() = 1">
          <xsl:text>first </xsl:text>
        </xsl:if>
        <xsl:if test="position() = last()">
          <xsl:text>last </xsl:text>
        </xsl:if>
        <xsl:text>level-1</xsl:text>
      </xsl:attribute>

      <a href="?q={@id}">
        <xsl:if test="description">
          <xsl:attribute name="title">
            <xsl:value-of select="description" />
          </xsl:attribute>
        </xsl:if>
        <xsl:value-of select="@name" />
        <xsl:if test="children/section">
          <ul>
            <xsl:apply-templates select="children/section" mode="menu" />
          </ul>
        </xsl:if>
      </a>
    </li>
  </xsl:template>

  <xsl:template match="node" mode="list">
    <h2>
      <xsl:value-of select="@name" />
    </h2>
    <xsl:if test="text">
      <xsl:value-of select="text" disable-output-escaping="yes" />
    </xsl:if>
  </xsl:template>

  <!-- Базовый шаблон для просмотра документа. -->
  <xsl:template match="widget[@class='DocWidget']">
    <div class="DocWidget">
      <h2>
        <a href="?q=node/{node/@id}">
          <xsl:value-of select="node/@name" />
        </a>
      </h2>
      <xsl:value-of select="node/@text" disable-output-escaping="yes" />
    </div>
  </xsl:template>
</xsl:stylesheet>
