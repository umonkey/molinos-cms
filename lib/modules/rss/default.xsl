<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output
    method="xml"
    encoding="utf-8"
    indent="no" />

  <xsl:template match="rss">
    <rss version="2.0">
      <xsl:apply-templates select="." mode="channel" />
    </rss>
  </xsl:template>

  <xsl:template match="rss" mode="channel">
    <channel>
      <title>
        <xsl:value-of select="@title" />
      </title>
      <link>
        <xsl:value-of select="@base" />
      </link>
      <description>
        <xsl:value-of select="@description" />
      </description>
      <generator>
        <xsl:text>Molinos CMS</xsl:text>
      </generator>
      <language>
        <xsl:value-of select="@language" />
      </language>

      <xsl:apply-templates select="nodes/node" />
    </channel>
  </xsl:template>

  <xsl:template match="node">
    <item>
      <xsl:apply-templates select="@name|@id|uid|text" mode="item" />
      <title>
        <xsl:value-of select="@name" />
      </title>

      <!--
        pubDate
        http://asg.web.cmu.edu/rfc/rfc822.html#sec-5
      -->

      <xsl:for-each select="*[@class='file']">
        <xsl:call-template name="enclosure" />
      </xsl:for-each>
    </item>
  </xsl:template>

  <xsl:template match="@id" mode="item">
    <link>
      <xsl:value-of select="/rss/@base" />
      <xsl:text>node/</xsl:text>
      <xsl:value-of select="." />
    </link>
    <guid>
      <xsl:value-of select="/rss/@base" />
      <xsl:text>node/</xsl:text>
      <xsl:value-of select="." />
    </guid>
  </xsl:template>

  <xsl:template match="uid" mode="item">
    <author>
      <xsl:value-of select="@name" />
    </author>
  </xsl:template>

  <xsl:template match="text" mode="item">
    <description>
      <xsl:value-of select="text()" />
    </description>
  </xsl:template>

  <xsl:template name="enclosure">
    <enclosure
      url="{/rss/@base}{versions/version[@name='original']/@url}"
      length="{filesize}"
      type="{filetype}"
      />
  </xsl:template>
</xsl:stylesheet>
