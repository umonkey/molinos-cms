<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/xsl/list.xsl" />

  <xsl:variable name="permitted" select="document(concat($api,'taxonomy/permitted.xml'))/sections" />

  <xsl:template match="data[../@name='list' and ../@preset='taxonomy']" mode="nodelist">
    <ul class="taxonomy nodes">
      <!--
      <xsl:apply-templates select="document(concat($api,'node/tree.xml?type=tag'))/node" mode="tagtree" />
      -->
      <xsl:apply-templates select="node" mode="tagtree" />
    </ul>
  </xsl:template>

  <!-- вывод разделов -->
  <xsl:template match="node" mode="tagtree">
    <xsl:variable name="id" select="@id" />
    <xsl:variable name="me" select="$permitted/section[@id=$id]" />
    <xsl:variable name="pid" select="../@id" />
    <xsl:variable name="parent" select="$permitted/section[@id=$pid]" />
    <li>
      <xsl:attribute name="class">
        <xsl:if test="@published">
          <xsl:text> published</xsl:text>
        </xsl:if>
        <xsl:if test="not(@published)">
          <xsl:text> unpublished</xsl:text>
        </xsl:if>
        <xsl:if test="position() = last()">
          <xsl:text> last</xsl:text>
        </xsl:if>
        <xsl:if test="node">
          <xsl:text> with-children</xsl:text>
        </xsl:if>
      </xsl:attribute>

      <xsl:if test="node">
        <span class="expand-collapse" />
      </xsl:if>
      
      <span class="container">
        <!--
        <a title="Найти все документы из этого раздела" href="admin/content/list?search=tags%3A{@id}">
        -->
        <a href="admin/node/{@id}?destination={$back}">
          <xsl:attribute name="class">
          <xsl:text>picker</xsl:text>
            <xsl:if test="not(@published)">
              <xsl:text> unpublished</xsl:text>
            </xsl:if>
          </xsl:attribute>
          <xsl:value-of select="@name" />
          <xsl:if test="not(@name)">
            <xsl:text>(без названия)</xsl:text>
          </xsl:if>
        </a>
        <span class="actions">
          <xsl:if test="$parent/@edit">
            <input type="checkbox" name="selected[]" value="{@id}" />
          </xsl:if>
          <xsl:if test="$me/@edit">
            <a title="Добавить подраздел" class="icon tag-add" href="admin/create/tag/{@id}?destination={$back}"></a>
          </xsl:if>
          <xsl:if test="$parent/@edit">
            <xsl:if test="position() != 1"><a title="Поднять раздел" class="icon tag-up" href="nodeapi.rpc?action=raise&amp;node={@id}&amp;destination={/page/@back}"></a></xsl:if>
            <xsl:if test="position() != last()"><a title="Опустить раздел" class="icon tag-down" href="nodeapi.rpc?action=sink&amp;node={@id}&amp;destination={/page/@back}"></a></xsl:if>
          </xsl:if>
          <xsl:if test="$me/@edit">
            <a title="Изменить свойства раздела" class="icon tag-edit" href="admin/edit/{@id}?destination={/page/@back}"></a>
          </xsl:if>
        </span>
      </span>
      <xsl:if test="node">
        <ul>
          <xsl:apply-templates select="node" mode="tagtree" />
        </ul>
      </xsl:if>
    </li>
  </xsl:template>
</xsl:stylesheet>
