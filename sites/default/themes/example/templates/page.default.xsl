<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
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
        <div id="content">
          <h1 id="logo">
            <a href=".">
              <span>Molinos CMS</span>
            </a>
          </h1>

          <xsl:apply-templates select="widgets/widget" />
        </div>
      </body>
    </html>
  </xsl:template>


  <!-- Базовый шаблон для списка документов -->
  <xsl:template match="widget[@class='ListWidget']">
    <div class="ListWidget">
      <ul class="nodes">
        <xsl:apply-templates select="documents/node" mode="ListWidget" />
      </ul>
    </div>
  </xsl:template>

  <xsl:template match="node" mode="ListWidget">
    <li>
      <h2>
        <a href="?q=node/{@id}">
          <xsl:value-of select="@name" />
        </a>
      </h2>
      <div class="teaser">
        <xsl:if test="@text">
          <xsl:value-of select="@text" disable-output-escaping="yes" />
        </xsl:if>
        <xsl:if test="not(@text)">
          <xsl:value-of select="@teaser" disable-output-escaping="yes" />
        </xsl:if>
      </div>
    </li>
  </xsl:template>


  <!-- Базовый шаблон для виджета "меню". -->
  <xsl:template match="widget[@class='MenuWidget']">
    <div class="MenuWidget">
      <ul>
        <xsl:apply-templates select="section" mode="MenuWidget" />
      </ul>
    </div>
  </xsl:template>

  <xsl:template match="section" mode="MenuWidget">
    <li>
      <a href="{@_link}">
        <xsl:value-of select="@name" />
      </a>
    </li>
  </xsl:template>


  <!-- Базовый шаблон для просмотра документа. -->
  <xsl:template match="widget[@class='DocWidget']">
    <div class="DocWidget">
      <h2>
        <a href="?q=node/{document/@id}">
          <xsl:value-of select="document/@displayName" />
        </a>
      </h2>
      <div class="content">
        <xsl:value-of select="document/@text" disable-output-escaping="yes" />
      </div>
    </div>
  </xsl:template>
</xsl:stylesheet>
