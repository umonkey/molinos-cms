<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <!-- рабочий стол -->
  <xsl:template match="content[@name='dashboard']" mode="content">
    <div id="dashboard">
      <form class="tabbed">
        <xsl:for-each select="content" mode="dashboard">
          <fieldset id="dashboard-{@name}" class="tabable">
            <legend>
              <span class="title">
                <xsl:value-of select="@title" />
              </span>
              <!--
              <span class="more">
                <xsl:if test="@more">
                  <xsl:text>(</xsl:text>
                  <a href="{@more}">ещё</a>
                  <xsl:text>)</xsl:text>
                </xsl:if>
              </span>
              -->
            </legend>
            <div>
              <xsl:apply-templates select="." mode="dashboard" />
            </div>
          </fieldset>
        </xsl:for-each>
      </form>
    </div>
  </xsl:template>

  <xsl:template match="content[@name='create']" mode="dashboard">
    <xsl:for-each select="node[not(isdictionary)]">
      <xsl:sort select="title" />
      <a class="create-{@name}" href="?q=admin/create/{@name}?destination={/page/@back}">
        <xsl:if test="description">
          <xsl:attribute name="title">
            <xsl:value-of select="description" />
          </xsl:attribute>
        </xsl:if>
        <span>
          <xsl:value-of select="title" />
        </span>
      </a>
    </xsl:for-each>
  </xsl:template>

  <xsl:template match="content[@name='status']" mode="dashboard">
    <xsl:if test="message">
      <ol>
        <xsl:for-each select="message">
          <a href="{@link}">
            <xsl:value-of select="text()" />
          </a>
        </xsl:for-each>
      </ol>
    </xsl:if>
  </xsl:template>

  <xsl:template match="content" mode="dashboard">
    <ol class="doclist">
      <xsl:for-each select="node">
        <li>
          <a href="?q=admin/edit/{@id}&amp;destination={/page/@back}">
            <xsl:value-of select="@name" />
            <xsl:if test="not(@name)">
              <xsl:text>(без названия)</xsl:text>
            </xsl:if>
          </a>
        </li>
      </xsl:for-each>
    </ol>
    <!--
    <p>
      <a href="{@more}">
        <xsl:text>Полный список</xsl:text>
      </a>
    </p>
    -->
  </xsl:template>
</xsl:stylesheet>
