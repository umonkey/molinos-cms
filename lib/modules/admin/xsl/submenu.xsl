<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="content[@type='submenu']" mode="content">
    <div class="submenu">
      <h2>
        <xsl:value-of select="@title" />
      </h2>

      <xsl:apply-templates select="." mode="list" />
      <xsl:apply-templates select="path[path]" mode="submenu" />
    </div>
  </xsl:template>

  <xsl:template match="path" mode="submenu">
    <xsl:if test="path">
      <fieldset>
        <legend>
          <xsl:choose>
            <xsl:when test="@name">
              <a href="{@name}">
                <xsl:value-of select="@title" />
              </a>
            </xsl:when>
          </xsl:choose>
        </legend>
        <xsl:apply-templates select="." mode="list" />
      </fieldset>
    </xsl:if>
  </xsl:template>

  <xsl:template match="path|content" mode="list">
    <xsl:if test="@description">
      <p>
        <xsl:value-of select="@description" />
      </p>
    </xsl:if>
    <ul>
      <xsl:for-each select="path[not(path)]">
        <xsl:sort select="@sort" />
        <xsl:sort select="@title" />
        <li>
          <a href="{@name}">
            <xsl:value-of select="@title" />
          </a>
          <xsl:if test="@description">
            <p>
              <xsl:value-of select="@description" />
            </p>
          </xsl:if>
        </li>
      </xsl:for-each>
    </ul>
  </xsl:template>
</xsl:stylesheet>
