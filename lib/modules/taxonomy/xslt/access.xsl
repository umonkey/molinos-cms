<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:variable name="groups" select="document(concat($api,'node/list.xml?class=group&amp;limit=1000'))/nodes" />

  <xsl:template match="content" mode="content">
    <h2>Права на разделы</h2>

    <form method="post" action="api/taxonomy/access.rpc">
      <table class="classic">
        <thead>
          <tr>
            <!--
            <th><input type="checkbox" name="sections[]" /></th>
            -->
            <th/>
            <th>Раздел</th>
            <th title="Могут модифицировать сами разделы">Владельцы</th>
            <th title="Могут размещать документы в разделах">Публиковать могут</th>
          </tr>
        </thead>
        <tbody>
          <xsl:apply-templates select="document(concat($api,'taxonomy/access.xml'))/sections/section" />
        </tbody>
        <!--
        <tfoot>
          <tr class="okbtn">
            <td colspan="4">
              <input type="submit" value="Изменить" />
              <xsl:if test="$args/@destination">
                <xsl:text> или </xsl:text>
                <a href="{$args/@destination}">вернуться</a>
              </xsl:if>
            </td>
          </tr>
        </tfoot>
        -->
      </table>
      <label class="control">
        <span class="t">Изменять структуру могут:</span>
        <select name="owners">
          <xsl:for-each select="$groups/node">
            <option value="{@id}">
              <xsl:value-of select="@name" />
            </option>
          </xsl:for-each>
        </select>
      </label>
      <label class="control">
        <span class="t">Публиковать в разделы могут:</span>
        <select name="publishers">
          <xsl:for-each select="$groups/node">
            <option value="{@id}">
              <xsl:value-of select="@name" />
            </option>
          </xsl:for-each>
        </select>
      </label>
      <input type="submit" value="Применить" class="control button" />
    </form>
  </xsl:template>

  <xsl:template match="nodes" mode="link">
    <xsl:param name="current" />
    <xsl:value-of select="$current" />
  </xsl:template>

  <xsl:template match="nodes">
    <xsl:param name="node" />
    <xsl:param name="current" />
    <xsl:param name="mode">wtf</xsl:param>
    <select name="section[{$node}][{$mode}]">
      <xsl:for-each select="node">
        <xsl:sort select="@name" />
        <option value="{@id}">
          <xsl:if test="@id=$current">
            <xsl:attribute name="selected">
              <xsl:text>selected</xsl:text>
            </xsl:attribute>
          </xsl:if>
          <xsl:value-of select="@name" />
        </option>
      </xsl:for-each>
    </select>
  </xsl:template>

  <xsl:template match="section">
    <xsl:param name="depth">4</xsl:param>
    <tr>
      <td>
        <input type="checkbox" name="sections[]" value="{@id}" id="check-{@id}" />
      </td>
      <td style="padding-left:{$depth}px">
        <label for="check-{@id}">
          <xsl:value-of select="@name" />
        </label>
      </td>
      <td>
        <xsl:variable name="o" select="@owners" />
        <xsl:value-of select="$groups/node[@id=$o]/@name" />
      </td>
      <td>
        <xsl:variable name="p" select="@publishers" />
        <xsl:value-of select="$groups/node[@id=$p]/@name" />
      </td>
    </tr>
    <xsl:apply-templates select="section">
      <xsl:with-param name="depth" select="$depth + 10" />
    </xsl:apply-templates>
  </xsl:template>
</xsl:stylesheet>
