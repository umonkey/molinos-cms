<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:variable name="node" select="document(concat($api,'node/get.xml?id=',$args/@node))/node" />
  <xsl:variable name="enabled" select="document(concat($api,'taxonomy/enabled.xml?node=',$args/@node))/nodes" />
  <xsl:variable name="selected" select="document(concat($api,'taxonomy/selected.xml?node=',$args/@node))/nodes" />

  <xsl:template match="content" mode="content">
    <h2>
      <xsl:apply-templates select="$node" mode="displayName" />
      <xsl:text>: привязка к разделам</xsl:text>
    </h2>

    <xsl:choose>
      <xsl:when test="$node/@class = 'type'">
        <p>
          Выберите, в какие разделы можно будет помещать <a href="admin/content/list/{$node/@name}">документы этого типа</a>.
          Вы также можете <a href="admin/system/settings/taxonomy?destination={$back}">указать</a>, какие документы можно помещать в один раздел, а какие — в несколько.
        </p>
      </xsl:when>
      <xsl:when test="$enabled/node">
        <p>В каких <a href="admin/structure/taxonomy">разделах</a> следует разместить этот документ?</p>
      </xsl:when>
      <xsl:when test="$enabled/@typeid">
        <p>
          Этот документ нельзя поместить ни в один раздел.
          <a href="admin/structure/taxonomy/setup?node={$enabled/@typeid}&amp;destination={$back}">Изменить это</a>?
        </p>
      </xsl:when>
    </xsl:choose>

    <xsl:if test="$enabled/node">
      <form method="post">
        <table class="classic" id="nodesections">
          <thead>
            <xsl:call-template name="okbtn" />
          </thead>
          <tbody>
            <xsl:apply-templates select="document(concat($api,'node/tree.xml?type=tag'))/node" mode="treeTable">
              <xsl:with-param name="type">
                <xsl:choose>
                  <xsl:when test="not($enabled/@multiple)">radio</xsl:when>
                  <xsl:otherwise>checkbox</xsl:otherwise>
                </xsl:choose>
              </xsl:with-param>
            </xsl:apply-templates>
          </tbody>
          <tfoot>
            <xsl:call-template name="okbtn" />
          </tfoot>
        </table>
      </form>
    </xsl:if>

    <xsl:if test="$enabled/@typeid">
      <p>
        Видны только разделы, в которые можно помещать документы этого типа.
        Вы можете <a href="admin/structure/taxonomy/setup?node={$enabled/@typeid}&amp;destination={$back}">изменить этот список</a>.
      </p>
    </xsl:if>
  </xsl:template>

  <xsl:template name="okbtn">
    <tr class="okbtn">
      <td>
        <xsl:if test="$enabled/@multiple">
          <input type="checkbox" class="toggle" />
        </xsl:if>
      </td>
      <td>
        <input type="submit" value="Применить" /> <input type="reset" value="Вернуть как было" /> или <a href="{$next}">вернуться</a>
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="node" mode="treeTable">
    <xsl:param name="pad" />
    <xsl:param name="type">checkbox</xsl:param>

    <xsl:variable name="id" select="@id" />

    <xsl:if test="$enabled/node[@id=$id]">
      <tr>
        <th>
          <xsl:if test="$enabled/node[@id=$id]">
            <input type="{$type}" value="{@id}" name="selected[]" id="cb-{@id}">
              <xsl:if test="$selected/node[@id=$id]">
                <xsl:attribute name="checked">checked</xsl:attribute>
              </xsl:if>
            </input>
          </xsl:if>
        </th>
        <td>
          <label for="cb-{@id}">
            <xsl:value-of select="$pad" />
            <a href="admin/node/{@id}">
              <xsl:apply-templates select="." mode="displayName" />
            </a>
          </label>
        </td>
      </tr>
    </xsl:if>
    <xsl:apply-templates select="node" mode="treeTable">
      <xsl:with-param name="pad" select="concat($pad,'&#160;&#160;')" />
      <xsl:with-param name="type" select="$type" />
    </xsl:apply-templates>
  </xsl:template>
</xsl:stylesheet>
