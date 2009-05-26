<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="list.xsl" />

  <xsl:template match="content" mode="content">
    <div class="doclist">
      <h2>
        <xsl:value-of select="@title" />
      </h2>

      <xsl:choose>
        <xsl:when test="not(data/node) and not(not($search))">
          <p>Нет таких документов, <a href="{$query}">показать все</a>?</p>
        </xsl:when>
        <xsl:when test="not(data/node)">
          <p>Здесь ничего нет, вообще.</p>
          <xsl:if test="@type">
            <p>
              <a href="admin/create/{@type}">Создать</a>
            </p>
          </xsl:if>
        </xsl:when>
        <xsl:otherwise>
          <xsl:if test="not(@nosearch)">
            <xsl:call-template name="mcms_list_search">
              <xsl:with-param name="advanced" select="@advsearch" />
            </xsl:call-template>
          </xsl:if>

          <!-- action проставляется скриптом lib/modules/admin/scripts/admin/10massctl.js -->
          <form method="post" id="nodeList">
            <input type="hidden" name="sendto" value="{$sendto}" />
            <xsl:apply-templates select="data" mode="massctl">
              <xsl:with-param name="delete" select="1" />
              <xsl:with-param name="hide" select="0" />
              <xsl:with-param name="publish" select="0" />
              <xsl:with-param name="restore" select="1" />
            </xsl:apply-templates>
            <table class="nodes">
              <xsl:apply-templates select="data" mode="nodelist" />
            </table>
            <xsl:apply-templates select="data" mode="massctl">
              <xsl:with-param name="delete" select="1" />
              <xsl:with-param name="hide" select="0" />
              <xsl:with-param name="publish" select="0" />
              <xsl:with-param name="restore" select="1" />
            </xsl:apply-templates>
          </form>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>
</xsl:stylesheet>
