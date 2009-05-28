<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="node[@class='file']" mode="thumbnail">
    <xsl:param name="size" select="100" />
    <img width="{$size}" height="{$size}" alt="{filename}">
      <xsl:attribute name="src">
        <xsl:choose>
          <xsl:when test="contains(filetype,'image/') and versions/version[@width=$size]/@url">
            <xsl:value-of select="versions/version[@width=$size]/@url" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>lib/modules/files/mime/</xsl:text>
            <xsl:call-template name="mimeToSimpleType">
              <xsl:with-param name="mimetype" select="filetype" />
            </xsl:call-template>
            <xsl:text>-</xsl:text>
            <xsl:value-of select="$size" />
            <xsl:text>.png</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
    </img>
  </xsl:template>

  <xsl:template name="mimeToSimpleType">
    <xsl:param name="mimetype" />
    <xsl:choose>
      <xsl:when test="contains($mimetype, 'audio/')">audio</xsl:when>
      <xsl:when test="contains($mimetype, 'image/')">image</xsl:when>
      <xsl:when test="contains($mimetype, 'video/')">video</xsl:when>
      <xsl:when test="contains($mimetype, 'text/')">text</xsl:when>
      <xsl:when test="contains($mimetype, 'application/x-deb')">package</xsl:when>
      <xsl:when test="contains($mimetype, 'application/x-rar')">package</xsl:when>
      <xsl:when test="contains($mimetype, 'application/x-tar')">package</xsl:when>
      <xsl:when test="contains($mimetype, 'application/zip')">package</xsl:when>
      <xsl:otherwise>binary</xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
