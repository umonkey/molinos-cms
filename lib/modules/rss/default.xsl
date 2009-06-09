<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:atom="http://www.w3.org/2005/Atom">
  <xsl:output
    method="xml"
    encoding="utf-8"
    indent="yes" />

  <xsl:template match="rss">
    <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
      <xsl:apply-templates select="." mode="channel" />
    </rss>
  </xsl:template>

  <xsl:template match="rss" mode="channel">
    <channel>
      <atom:link rel="self" type="application/rss+xml" href="{/rss/@url}" />
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
      <xsl:apply-templates select="@name|@id|uid" mode="item" />
      <xsl:apply-templates select="." mode="description" />
      <xsl:apply-templates select="@created" mode="pubDate" />
      <xsl:for-each select="*[@filetype]">
        <xsl:call-template name="enclosure" />
      </xsl:for-each>
      <xsl:for-each select="files/node">
        <xsl:if test="not(contains(filetype,'image/'))">
          <enclosure
            url="{/rss/@base}download/{@id}/{filename}"
            length="{filesize}"
            type="{filetype}"
            />
        </xsl:if>
      </xsl:for-each>
      <xsl:if test="filetype">
        <enclosure
          url="{/rss/@base}download/{@id}/{filename}"
          length="{filesize}"
          type="{filetype}" />
      </xsl:if>
    </item>
  </xsl:template>

  <xsl:template match="@name" mode="item">
    <title>
      <xsl:value-of select="." />
    </title>
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
      <xsl:value-of select="email" />
      <xsl:value-of select="@email" />
      <xsl:if test="not(@email)">unknown@example.com</xsl:if>
      <xsl:text> (</xsl:text>
      <xsl:value-of select="@name" />
      <xsl:if test="not(@name)">
        <xsl:text>Anonymous Coward</xsl:text>
      </xsl:if>
      <xsl:text>)</xsl:text>
    </author>
  </xsl:template>

  <xsl:template match="node" mode="description">
    <description>
      <xsl:choose>
        <xsl:when test="text">
          <xsl:value-of select="text" />
        </xsl:when>
        <xsl:when test="body">
          <xsl:value-of select="body" />
        </xsl:when>
      </xsl:choose>
    </description>
  </xsl:template>

  <xsl:template match="text" mode="item">
    <description>
      <xsl:apply-templates select="../*/versions/version" />
      <xsl:value-of select="text()" />
    </description>
  </xsl:template>

  <xsl:template match="version">
    <!-- не работает :(
    <img src="{/rss/@base}{@url}" witdh="{@width}" height="{@height}" alt="{../@name}" />
    -->
  </xsl:template>

  <xsl:template name="enclosure">
    <xsl:if test="not(contains(@filetype,'image/'))">
      <enclosure
        url="{/rss/@base}download/{@id}/{@filename}"
        length="{@filesize}"
        type="{@filetype}"
        />
    </xsl:if>
  </xsl:template>

  <xsl:template match="@created" mode="pubDate">
    <!--
      http://asg.web.cmu.edu/rfc/rfc822.html#sec-5
      [Mon, ]09 Mar 2009 15:42:50 +0300
    -->
    <pubDate>
      <xsl:value-of select="substring(.,9,2)" />
      <xsl:text> </xsl:text>
      <xsl:choose>
        <xsl:when test="substring(.,6,2)='01'">
          <xsl:text>Jan</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='02'">
          <xsl:text>Feb</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='03'">
          <xsl:text>Mar</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='04'">
          <xsl:text>Apr</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='05'">
          <xsl:text>May</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='06'">
          <xsl:text>Jun</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='07'">
          <xsl:text>Jul</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='08'">
          <xsl:text>Aug</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='09'">
          <xsl:text>Sep</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='10'">
          <xsl:text>Oct</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='11'">
          <xsl:text>Nov</xsl:text>
        </xsl:when>
        <xsl:when test="substring(.,6,2)='12'">
          <xsl:text>Dec</xsl:text>
        </xsl:when>
      </xsl:choose>
      <xsl:text> </xsl:text>
      <xsl:value-of select="substring(.,1,4)" />
      <xsl:text> </xsl:text>
      <!-- вывод времени, если есть -->
      <xsl:choose>
        <xsl:when test="string-length(.) &lt; 19">
          <xsl:text>12:00:00</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="substring(.,12,8)" />
        </xsl:otherwise>
      </xsl:choose>
      <xsl:text> GMT</xsl:text>
    </pubDate>
  </xsl:template>
</xsl:stylesheet>
